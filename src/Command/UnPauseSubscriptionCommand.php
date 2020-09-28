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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class UnPauseSubscriptionCommand extends SubscriptionCommand
{
    private $subscriptions;

    public function __construct(Repository $subscriptions)
    {
        $this->subscriptions = $subscriptions;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('streak:subscription:unpause');
        $this->setDescription('Unpause single subscription');
        $this->setDefinition([
            new InputArgument('subscription-type', InputArgument::REQUIRED, 'Specify subscription type'),
            new InputArgument('subscription-id', InputArgument::REQUIRED, 'Specify subscription id'),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id = $this->subscriptionId($input);
        $subscription = $this->subscriptions->find($id);

        if (null === $subscription) {
            $output->writeln(sprintf('Subscription %s not found.', $this->formatSubscriptionId($id)));

            return;
        }

        try {
            $subscription->unpause();

            $output->writeln(sprintf('Subscription %s unpausing succeeded.', $this->formatSubscriptionId($id)));
        } catch (\Throwable $exception) {
            // lets write to stderr if possible
            if ($output instanceof ConsoleOutputInterface) {
                $output = $output->getErrorOutput(); // @codeCoverageIgnore
            }
            $output->writeln(sprintf('Subscription %s unpausing failed.', $this->formatSubscriptionId($id)));

            throw $exception;
        }
    }
}
