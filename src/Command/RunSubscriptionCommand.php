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

use Streak\Domain\Event\Subscription\Repository;
use Streak\Domain\EventStore;
use Streak\Domain\Id;
use Streak\Infrastructure\UnitOfWork;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class RunSubscriptionCommand extends Command
{
    private $subscriptions;
    private $store;
    private $uow;

    public function __construct(Repository $subscriptions, EventStore $store, UnitOfWork $uow)
    {
        $this->subscriptions = $subscriptions;
        $this->store = $store;
        $this->uow = $uow;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('streak:subscription:run');
        $this->setDescription('Runs single subscription (sagas, process managers, projectors, etc)');
        $this->setDefinition([
            new InputArgument('subscription-type', InputArgument::REQUIRED, 'Specify subscription type'),
            new InputArgument('subscription-id', InputArgument::REQUIRED, 'Specify subscription id'),
            new InputOption('events-commit-threshold', null, InputOption::VALUE_OPTIONAL, 'Number of events that listener can listen to before changes are committed', 1),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $threshold = (int) $input->getOption('events-commit-threshold');

        $subscription = $this->subscriptions->find($this->id($input));

        if (null === $subscription) {
            $output->writeln(sprintf('Subscription <fg=blue>%s</>(<fg=cyan>%s</>) not found.', $input->getArgument('subscription-type'), $input->getArgument('subscription-id')));

            return;
        }

        ProgressBar::setFormatDefinition('custom', 'Subscription <fg=blue>%subscription_type%</>(<fg=cyan>%subscription_id%</>) processed <fg=yellow>%current%</> events in <fg=magenta>%elapsed%</>.');

        // progress bar by default is using stderr, lets avoid that
        if ($output instanceof ConsoleOutputInterface) {
            $output = $output->section(); // @codeCoverageIgnore
        } elseif ($output instanceof StreamOutput) {
            $output = new StreamOutput($output->getStream(), $output->getVerbosity(), null, $output->getFormatter()); // @codeCoverageIgnore
        }

        $progress = new ProgressBar($output);
        $progress->setFormat('custom');
        $progress->setOverwrite(true);
        $progress->setMessage($input->getArgument('subscription-type'), 'subscription_type');
        $progress->setMessage($input->getArgument('subscription-id'), 'subscription_id');
        $progress->display();
        $output->writeln(''); // streak:subscriptions:run requires this for splitting the output

        try {
            $listened = 0;
            $this->uow->add($subscription);
            foreach ($subscription->subscribeTo($this->store) as $event) {
                ++$listened; // listened since last commit

                if ($listened === $threshold) { // time to commit
                    iterator_to_array($this->uow->commit());
                    $this->uow->add($subscription);

                    if ($output instanceof ConsoleSectionOutput) {
                        $output->clear(); // @codeCoverageIgnore
                    }
                    $progress->advance($listened);
                    $output->writeln('');

                    $listened = 0; // reset the counter
                }
            }

            if (0 !== $listened) { // some events left output
                if ($output instanceof ConsoleSectionOutput) {
                    $output->clear(); // @codeCoverageIgnore
                }
                $progress->advance($listened);
                $output->writeln('');
            }

            iterator_to_array($this->uow->commit());
        } finally {
            $this->uow->clear();
            if ($output instanceof ConsoleSectionOutput) {
                $output->clear(); // @codeCoverageIgnore
            }
            $progress->finish();
        }
    }

    private function id(InputInterface $input) : Id
    {
        $class = $input->getArgument('subscription-type');
        $value = $input->getArgument('subscription-id');

        return call_user_func([$class, 'fromString'], $value);
    }
}
