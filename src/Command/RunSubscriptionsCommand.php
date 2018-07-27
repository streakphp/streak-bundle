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
use Streak\Infrastructure\UnitOfWork;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class RunSubscriptionsCommand extends Command
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
        $this->setName('streak:subscriptions:run');
        $this->setDescription('Runs all subscriptions (sagas, process managers, projectors, etc)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $subscriptions = $this->subscriptions->all();

        foreach ($subscriptions as $subscription) {
            $this->uow->add($subscription);

            try {
                foreach ($subscription->subscribeTo($this->store) as $event) {
                    $this->uow->commit();
                    $this->uow->add($subscription);
                }
                $this->uow->commit();
            } catch (\Throwable $e) {
                $output->writeln($e->getMessage());
                $output->writeln($e->getTraceAsString());
            }
        }
    }
}
