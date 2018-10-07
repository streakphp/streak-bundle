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
    private $subscriptions;

    /**
     * @var Process[]
     */
    private $processes = [];

    /**
     * @var ConsoleSectionOutput[]
     */
    private $sections = [];

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
            new InputOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Specify how many subscriptions should be run at once', 10),
            new InputOption('php-executable', 'p', InputOption::VALUE_OPTIONAL, 'Specify "php" executable to run sub processes with', 'php'),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $types = (array) $input->getArgument('type');
        $limit = (int) $input->getOption('limit');
        $filter = Repository\Filter::nothing();

        if (count($types) > 0) {
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
            $command = $this->executable($command, $input);

            $this->processes[] = $process = new Process($command);
            $process->start();

            if ($output instanceof ConsoleOutputInterface) {
                $this->sections[] = $output->section();
            } else {
                $this->sections[] = $output;
            }

            while ($this->runningProcesses() >= $limit) {
                $this->output();
            }
        }

        while ($this->runningProcesses()) {
            $this->output();
        }

        $this->output();
    }

    private function runningProcesses() : int
    {
        $number = 0;
        foreach ($this->processes as $process) {
            if ($process->isRunning()) {
                ++$number;
            }
        }

        return $number;
    }

    private function command(Subscription $subscription) : array
    {
        $type = get_class($subscription->subscriptionId());
        $id = $subscription->subscriptionId()->toString();

        $command = ['bin/console', 'streak:subscription:run', $type, $id, '--no-ansi'];

        return $command;
    }

    private function verbosity(array $command, OutputInterface $output) : array
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

    private function executable(array $command, InputInterface $input) : array
    {
        $executable = $input->getOption('php-executable');

        array_unshift($command, $executable);

        return $command;
    }

    private function decorate(string $string) : string
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
    private function output() : void
    {
        foreach ($this->processes as $index => $process) {
            if (false === isset($this->sections[$index])) {
                continue;
            }
            $output = $this->sections[$index];

            $command = $process->getCommandLine();
            $command = explode(' ', $command);
            $command[0] = trim($command[0], "'"); // trim 'php' executable
            $command[1] = trim($command[1], "'"); // trim 'bin/console'
            $command[2] = trim($command[2], "'"); // trim 'streak:subscription:run'
            $command = implode(' ', $command);
            $command = '<fg=green>'.$command.'</>';

            $terminated = $process->isTerminated();
            $success = $process->isSuccessful();
            $buffer = $process->getOutput();
            $buffer = trim($buffer);

            if ('' !== $buffer) {
                $buffer = explode(PHP_EOL, $buffer);
                $buffer = array_pop($buffer);
                $buffer = $this->decorate($buffer);

                if ($output instanceof ConsoleSectionOutput) {
                    $output->clear();
                }
                // show only when -vv or -vvv
                $output->writeln('🍿 '.$command.' running', $output::VERBOSITY_VERY_VERBOSE);
                $output->writeln($buffer, $output::VERBOSITY_VERY_VERBOSE);
            }

            if (true === $terminated) {
                if (true === $success) {
                    $command = '✅ '.$command;
                } else {
                    $command = '🔥 '.$command;
                }

                $buffer = $process->getOutput();
                $buffer = trim($buffer);
                $buffer = explode(PHP_EOL, $buffer);
                $buffer = array_pop($buffer);
                $buffer = trim($buffer);
                $buffer = $this->decorate($buffer);

                if ($output instanceof ConsoleSectionOutput) {
                    $output->clear();
                }
                // show when not --quiet
                $output->writeln($command.' terminated', $output::VERBOSITY_NORMAL);
                $output->writeln($buffer, $output::VERBOSITY_NORMAL); // show when not quiet

                if (false === $success) {
                    $buffer = $process->getErrorOutput();
                    $buffer = trim($buffer);
                    $buffer = sprintf('<error>%s</error>', $buffer);

                    // show when not quiet
                    $output->writeln($buffer, $output::VERBOSITY_NORMAL);
                }
                unset($this->sections[$index]);
            }
        }
        usleep(500000); // wait 500ms between refreshing output
    }
}
