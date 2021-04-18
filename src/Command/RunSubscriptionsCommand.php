<?php

/**
 * This file is part of the streak-bundle package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Streak\StreakBundle\Command;

use Streak\Domain\Event\Subscription;
use Streak\Domain\Event\Subscription\Repository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @codeCoverageIgnore Unfortunately Process component is untestable.
 */
class RunSubscriptionsCommand extends Command
{
    public const TERMINAL_CLEAR_LINE = "\r\e[2K";

    private Repository $subscriptions;

    /**
     * @var Process[]
     */
    private array $processes = [];

    /**
     * @var ConsoleSectionOutput[]
     */
    private array $outputs = [];

    public function __construct(Repository $subscriptions)
    {
        $this->subscriptions = $subscriptions;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('streak:subscriptions:run');
        $this->setDescription('Runs all subscriptions (sagas, process managers, projectors, etc) in sub processes');
        $this->setDefinition([
            new InputArgument('type', InputArgument::IS_ARRAY, 'Specify types of subscriptions to run'),
            new InputOption('concurrency-limit', 'l', InputOption::VALUE_OPTIONAL, 'Specify how many subscriptions can be run concurrently at most', 10),
            new InputOption('listening-limit', null, InputOption::VALUE_OPTIONAL, 'Specify how many event can subscription listen to at most', null),
            new InputOption('php-executable', 'p', InputOption::VALUE_OPTIONAL, 'Specify "php" executable to run sub processes with', 'php'),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $types = (array) $input->getArgument('type');
        $limit = (int) $input->getOption('concurrency-limit');
        $filter = Repository\Filter::nothing();

        if (\count($types) > 0) {
            $filter = $filter->filterSubscriptionTypes(...$types);
        }

        $subscriptions = $this->subscriptions->all($filter);
        $subscriptions = new \IteratorIterator($subscriptions);

        $subscriptions->rewind();

        while ($subscriptions->valid()) {
            /* @var $subscription Subscription */
            $subscription = $subscriptions->current();
            $subscriptions->next();

            $command = $this->command($subscription);
            $command = $this->verbosity($command, $output);
            $command = $this->listeningLimit($command, $input);
            $command = $this->executable($command, $input);

            $this->processes[] = $process = new Process($command);
            $process->start();

            if ($output->getVerbosity() <= $output::VERBOSITY_VERBOSE) { // for verbosity below -vv we do not need real output sections
                $this->outputs[] = $output; // no flickering with simple output
            } elseif ($output instanceof ConsoleOutputInterface) {
                $this->outputs[] = $output->section();
            } else {
                $this->outputs[] = $output; // no flickering with simple output
            }

            while ($this->runningProcesses() >= $limit) {
                $this->output();
            }
        }

        while ($this->runningProcesses()) {
            $this->output();
        }

        $this->output();

        return 0;
    }

    private function runningProcesses(): int
    {
        $number = 0;
        foreach ($this->processes as $process) {
            if ($process->isRunning()) {
                ++$number;
            }
        }

        return $number;
    }

    private function command(Subscription $subscription): array
    {
        $type = \get_class($subscription->subscriptionId());
        $id = $subscription->subscriptionId()->toString();

        $command = ['bin/console', 'streak:subscription:run', $type, $id, '--no-ansi'];

        return $command;
    }

    private function verbosity(array $command, OutputInterface $output): array
    {
        if ($output->getVerbosity() === $output::VERBOSITY_QUIET) {
            $command[] = '--quiet';
        }
        if ($output->getVerbosity() === $output::VERBOSITY_VERBOSE) {
            $command[] = '-v';
        }
        if ($output->getVerbosity() === $output::VERBOSITY_VERY_VERBOSE) {
            $command[] = '-vv';
        }
        if ($output->getVerbosity() === $output::VERBOSITY_DEBUG) {
            $command[] = '-vvv';
        }

        return $command;
    }

    private function listeningLimit(array $command, InputInterface $input): array
    {
        $limit = $input->getOption('listening-limit');

        if (null !== $limit) {
            $command[] = '--listening-limit='.(int) $limit;
        }

        return $command;
    }

    private function executable(array $command, InputInterface $input): array
    {
        $executable = $input->getOption('php-executable');

        array_unshift($command, $executable);

        return $command;
    }

    private function decorate(string $string): string
    {
        $pattern = '/Subscription (.+)\((.+)\) processed (.+) events in (.+)./i';
        $replacement = 'Subscription <fg=blue>$1</>(<fg=cyan>$2</>) processed <fg=yellow>$3</> events in <fg=magenta>$4</>.';
        $decorated = preg_replace($pattern, $replacement, $string);

        if (null === $decorated) {
            return $string;
        }

        return $decorated;
    }

    /**
     * TODO: clean this up.
     */
    private function output(): void
    {
        foreach ($this->processes as $index => $process) {
            if (false === isset($this->outputs[$index])) {
                continue;
            }
            $output = $this->outputs[$index];

            $command = $this->extractAndDecorateCommand($process);

            $terminated = $process->isTerminated();
            $success = $process->isSuccessful();
            $buffer = $process->getOutput();
            $buffer = trim($buffer);

            if ('' !== $buffer) {
                $buffer = explode(self::TERMINAL_CLEAR_LINE, $buffer); // split progress bar steps
                $buffer = array_pop($buffer);
                $buffer = $this->decorate($buffer);

                if ($output instanceof ConsoleSectionOutput) {
                    $output->clear();
                }
                $output->writeln('🍿 '.$command.' running', $output::VERBOSITY_VERY_VERBOSE); // show only when -vv or -vvv
                $output->writeln($buffer, $output::VERBOSITY_VERY_VERBOSE); // show only when -vv or -vvv
            }

            if (true === $terminated) {
                if (true === $success) {
                    $command = '✅ '.$command.' succeeded';
                } else {
                    if ($output instanceof ConsoleOutputInterface) {
                        $output = $output->getErrorOutput(); // when verbosity is -v we can actualy write to stderr
                    }
                    $command = '🔥 '.$command.' failed';
                }

                // lets display remaining stdout output...
                $buffer = $process->getOutput();
                $buffer = trim($buffer);
                $buffer = explode(self::TERMINAL_CLEAR_LINE, $buffer); // split progress bar steps
                $buffer = array_pop($buffer);
                $buffer = trim($buffer);
                $buffer = $this->decorate($buffer);

                if ($output instanceof ConsoleSectionOutput) {
                    $output->clear();
                }
                $output->writeln($command, $output::VERBOSITY_NORMAL); // show when not --quiet
                $output->writeln($buffer, $output::VERBOSITY_NORMAL); // show when not --quiet

                // ...and top it off with stderr output if any
                if (false === $success) {
                    $buffer = $process->getErrorOutput();
                    $buffer = trim($buffer);
                    $buffer = sprintf('<error>%s</error>', $buffer);

                    $output->writeln($buffer, $output::VERBOSITY_NORMAL); // show when not quiet
                }
                unset($this->outputs[$index]);
            }
        }
        usleep(500000); // wait 500ms between refreshing output
    }

    private function extractAndDecorateCommand(Process $process): string
    {
        $command = $process->getCommandLine();
        $command = explode(' ', $command);
        $command[0] = trim($command[0], "'"); // trim 'php' executable
        $command[1] = trim($command[1], "'"); // trim 'bin/console'
        $command[2] = trim($command[2], "'"); // trim 'streak:subscription:run'
        $command = implode(' ', $command);
        $command = '<fg=green>'.$command.'</>';

        return $command;
    }
}
