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
use Streak\Domain\Event\Subscription\Exception\SubscriptionRestartNotPossible;
use Streak\Domain\Event\Subscription\Repository;
use Streak\Infrastructure\UnitOfWork;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class RestartSubscriptionsCommand extends Command
{
    private $subscriptions;
    private $uow;

    public function __construct(Repository $subscriptions, UnitOfWork $uow)
    {
        $this->subscriptions = $subscriptions;
        $this->uow = $uow;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('streak:subscriptions:restart');
        $this->setDescription('Restarts all subscriptions that underlying listener can be reset.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $subscriptions = $this->subscriptions->all();

        foreach ($subscriptions as $subscription) {
            try {
                $subscription->restart();

                iterator_to_array($this->uow->commit());

                $output->writeln(sprintf('Subscription <info>%s</info> restart succeeded.', $this->name($subscription)));
            } catch (SubscriptionRestartNotPossible $exception) {
                $output->writeln(sprintf('Subscription <info>%s</info> restart not supported.', $this->name($subscription)));

                continue; // next subscriptions
            } catch (\Throwable $exception) {
                $output->writeln(sprintf('Subscription <info>%s</info> restart failed with <error>"%s"</error>.', $this->name($subscription), $exception->getMessage()));

                continue;
            } finally {
                $this->uow->clear();
            }
        }
    }

    private function name(Subscription $subscription) : string
    {
        $id = $subscription->subscriptionId();

        return sprintf('%s(%s)', get_class($id), $id->toString());
    }
}
