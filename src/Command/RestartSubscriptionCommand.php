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

use Streak\Domain\Event\Subscription\Exception\SubscriptionRestartNotPossible;
use Streak\Domain\Event\Subscription\Repository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\StreakBundle\Tests\Command\RestartSubscriptionCommandTest
 */
class RestartSubscriptionCommand extends SubscriptionCommand
{
    private Repository $subscriptions;

    public function __construct(Repository $subscriptions)
    {
        $this->subscriptions = $subscriptions;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('streak:subscription:restart');
        $this->setDescription('Restart single subscription');
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

            return 0;
        }

        try {
            $subscription->restart();

            $output->writeln(sprintf('Subscription %s restart succeeded.', $this->formatSubscriptionId($id)));
        } catch (SubscriptionRestartNotPossible $exception) {
            $output->writeln(sprintf('Subscription %s restart not supported.', $this->formatSubscriptionId($id)));
        } catch (\Throwable $exception) {
            // lets write to stderr if possible
            if ($output instanceof ConsoleOutputInterface) {
                $output = $output->getErrorOutput(); // @codeCoverageIgnore
            }
            $output->writeln(sprintf('Subscription %s restart failed.', $this->formatSubscriptionId($id)));

            throw $exception;
        }

        return 0;
    }
}
