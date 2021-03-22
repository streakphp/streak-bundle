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
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\StreakBundle\Tests\Command\RunSubscriptionCommandTest
 */
class RunSubscriptionCommand extends SubscriptionCommand
{
    private Repository $subscriptions;
    private EventStore $store;

    public function __construct(Repository $subscriptions, EventStore $store)
    {
        $this->subscriptions = $subscriptions;
        $this->store = $store;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('streak:subscription:run');
        $this->setDescription('Runs single subscription (sagas, process managers, projectors, etc)');
        $this->setDefinition([
            new InputArgument('subscription-type', InputArgument::REQUIRED, 'Specify subscription type'),
            new InputArgument('subscription-id', InputArgument::REQUIRED, 'Specify subscription id'),
            new InputOption('pause-on-error', '', InputOption::VALUE_NONE, 'Pause subscription on error'),
            new InputOption('listening-limit', 'l', InputOption::VALUE_OPTIONAL, 'Maximum number of events subscription can listen to', null),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ProgressBar::setFormatDefinition('custom', 'Subscription <fg=blue>%subscription_type%</>(<fg=cyan>%subscription_id%</>) processed <fg=yellow>%current%</> events in <fg=magenta>%elapsed%</>.');

        // progress bar by default is writing to stderr, lets try to mitigate that
        if ($output instanceof StreamOutput) {
            $output = new StreamOutput($output->getStream(), $output->getVerbosity(), null, $output->getFormatter()); // @codeCoverageIgnore
        }

        // by instantiating progress bar here we start measuring time before we load subscription,
        // so elapsed time will include subscription initialization period
        $progress = new ProgressBar($output);
        $progress->setFormat('custom');
        $progress->setOverwrite(true);
        $progress->setMessage($input->getArgument('subscription-type'), 'subscription_type');
        $progress->setMessage($input->getArgument('subscription-id'), 'subscription_id');

        $subscription = $this->subscriptions->find($this->subscriptionId($input));

        if (null === $subscription) {
            $output->write(sprintf('Subscription <fg=blue>%s</>(<fg=cyan>%s</>) not found.', $input->getArgument('subscription-type'), $input->getArgument('subscription-id')));

            return 0;
        }

        $limit = $input->getOption('listening-limit');

        if (null !== $limit) {
            $limit = (int) $limit;
        }

        $progress->display();

        try {
            foreach ($subscription->subscribeTo($this->store, $limit) as $event) {
                $progress->advance();
            }
        } catch (\Throwable $exception) {
            if (true === $input->getOption('pause-on-error')) {
                $subscription->pause();
            }

            throw $exception;
        } finally {
            $progress->finish();
        }

        return 0;
    }
}
