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

use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Subscription\Repository;
use Streak\Domain\EventStore;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class RunSubscriptionCommand extends Command
{
    private $subscriptions;
    private $store;

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
            new InputOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Maximum number of events subscription can listen to', 1000),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $subscription = $this->subscriptions->find($this->id($input));

        if (null === $subscription) {
            $output->write(sprintf('Subscription <fg=blue>%s</>(<fg=cyan>%s</>) not found.', $input->getArgument('subscription-type'), $input->getArgument('subscription-id')));

            return;
        }

        $limit = (int) $input->getOption('limit');

        ProgressBar::setFormatDefinition('custom', 'Subscription <fg=blue>%subscription_type%</>(<fg=cyan>%subscription_id%</>) processed <fg=yellow>%current%</> events in <fg=magenta>%elapsed%</>.');

        // progress bar by default is writing to stderr, lets try to mitigate that
        if ($output instanceof StreamOutput) {
            $output = new StreamOutput($output->getStream(), $output->getVerbosity(), null, $output->getFormatter()); // @codeCoverageIgnore
        }

        $progress = new ProgressBar($output);
        $progress->setFormat('custom');
        $progress->setOverwrite(true);
        $progress->setMessage($input->getArgument('subscription-type'), 'subscription_type');
        $progress->setMessage($input->getArgument('subscription-id'), 'subscription_id');
        $progress->display();

        try {
            foreach ($subscription->subscribeTo($this->store, $limit) as $event) {
                $progress->advance();
            }
        } finally {
            $progress->finish();
        }
    }

    private function id(InputInterface $input) : Listener\Id
    {
        $class = $input->getArgument('subscription-type');

        try {
            $reflection = new \ReflectionClass($class);
        } catch (\ReflectionException $exception) {
            throw new \InvalidArgumentException(sprintf('Given "subscription-type" argument "%s" is not a type of "%s"', $class, Listener\Id::class));
        }

        if (false === $reflection->implementsInterface(Listener\Id::class)) {
            throw new \InvalidArgumentException(sprintf('Given "subscription-type" argument "%s" is not a type of "%s"', $class, Listener\Id::class));
        }

        $id = (string) $input->getArgument('subscription-id');

        try {
            return $reflection->getMethod('fromString')->invoke(null, $id);
        } catch (\Throwable $exception) {
            throw new \InvalidArgumentException(sprintf('Given "subscription-id" argument "%s" is invalid', $id));
        }
    }
}
