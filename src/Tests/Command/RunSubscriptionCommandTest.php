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

namespace Streak\StreakBundle\Tests\Command;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\Event\Subscription\Repository;
use Streak\Domain\EventStore;
use Streak\Infrastructure\UnitOfWork;
use Streak\StreakBundle\Command\RunSubscriptionCommand;
use Streak\StreakBundle\Tests\Command\RunSubscriptionCommandTest\SubscriptionId1;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\StreakBundle\Command\RunSubscriptionCommand
 */
class RunSubscriptionCommandTest extends TestCase
{
    /**
     * @var Repository|MockObject
     */
    private $repository;

    /**
     * @var EventStore|MockObject
     */
    private $store;

    /**
     * @var UnitOfWork|MockObject
     */
    private $uow;

    /**
     * @var Event\Subscription|MockObject
     */
    private $subscription1;

    /**
     * @var Event|MockObject
     */
    private $event1;

    /**
     * @var Event|MockObject
     */
    private $event2;

    /**
     * @var OutputInterface|MockObject
     */
    private $output;

    public function setUp()
    {
        $this->repository = $this->getMockBuilder(Repository::class)->getMockForAbstractClass();
        $this->store = $this->getMockBuilder(EventStore::class)->getMockForAbstractClass();
        $this->uow = $this->getMockBuilder(UnitOfWork::class)->getMockForAbstractClass();

        $this->subscription1 = $this->getMockBuilder([Event\Subscription::class, Event\Producer::class])->getMock();

        $this->event1 = $this->getMockBuilder(Event::class)->setMockClassName('event1')->getMockForAbstractClass();
        $this->event2 = $this->getMockBuilder(Event::class)->setMockClassName('event2')->getMockForAbstractClass();

        $this->output = new RunSubscriptionCommandTest\TestOutput();
    }

    public function testCommand()
    {
        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(SubscriptionId1::fromString('EC2BE294-C07A-4198-A159-4551686F14F9'))
            ->willReturn($this->subscription1)
        ;

        $this->subscription1
            ->expects($this->once())
            ->method('subscribeTo')
            ->with($this->store)
            ->willReturn([$this->event1, $this->event2])
        ;

        $this->uow
            ->expects($this->exactly(3))
            ->method('commit')
        ;
        $this->uow
            ->expects($this->exactly(1))
            ->method('clear')
        ;

        $command = new RunSubscriptionCommand($this->repository, $this->store, $this->uow);
        $command->run(new ArrayInput(['subscription-type' => 'Streak\\StreakBundle\\Tests\\Command\\RunSubscriptionCommandTest\\SubscriptionId1', 'subscription-id' => 'EC2BE294-C07A-4198-A159-4551686F14F9']), $this->output);

        $expected = <<<OUTPUT
Subscription Streak\StreakBundle\Tests\Command\RunSubscriptionCommandTest\SubscriptionId1(EC2BE294-C07A-4198-A159-4551686F14F9) processed    0 events in < 1 sec.
\r\e[2KSubscription Streak\StreakBundle\Tests\Command\RunSubscriptionCommandTest\SubscriptionId1(EC2BE294-C07A-4198-A159-4551686F14F9) processed    1 events in < 1 sec.
\r\e[2KSubscription Streak\StreakBundle\Tests\Command\RunSubscriptionCommandTest\SubscriptionId1(EC2BE294-C07A-4198-A159-4551686F14F9) processed    2 events in < 1 sec.
\r\e[2KSubscription Streak\StreakBundle\Tests\Command\RunSubscriptionCommandTest\SubscriptionId1(EC2BE294-C07A-4198-A159-4551686F14F9) processed    2 events in < 1 sec.
OUTPUT;
        $this->assertEquals($expected, $this->output->output);
    }

    public function testNotFound()
    {
        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(SubscriptionId1::fromString('EC2BE294-C07A-4198-A159-4551686F14F9'))
            ->willReturn(null)
        ;

        $command = new RunSubscriptionCommand($this->repository, $this->store, $this->uow);
        $command->run(new ArrayInput(['subscription-type' => 'Streak\\StreakBundle\\Tests\\Command\\RunSubscriptionCommandTest\\SubscriptionId1', 'subscription-id' => 'EC2BE294-C07A-4198-A159-4551686F14F9']), $this->output);

        $expected = <<<OUTPUT
Subscription Streak\StreakBundle\Tests\Command\RunSubscriptionCommandTest\SubscriptionId1(EC2BE294-C07A-4198-A159-4551686F14F9) not found.\n
OUTPUT;
        $this->assertEquals($expected, $this->output->output);
    }

    public function testError()
    {
        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(SubscriptionId1::fromString('EC2BE294-C07A-4198-A159-4551686F14F9'))
            ->willReturn($this->subscription1)
        ;

        $this->subscription1
            ->expects($this->once())
            ->method('subscribeTo')
            ->with($this->store)
            ->willReturnCallback(function () {
                yield $this->event1;
                yield $this->event2;
                throw new \RuntimeException('Test exception thrown');
            })
        ;

        $this->uow
            ->expects($this->exactly(2))
            ->method('commit')
        ;
        $this->uow
            ->expects($this->exactly(1))
            ->method('clear')
        ;

        $this->expectExceptionObject(new \RuntimeException('Test exception thrown'));

        try {
            $command = new RunSubscriptionCommand($this->repository, $this->store, $this->uow);
            $command->run(new ArrayInput(['subscription-type' => 'Streak\\StreakBundle\\Tests\\Command\\RunSubscriptionCommandTest\\SubscriptionId1', 'subscription-id' => 'EC2BE294-C07A-4198-A159-4551686F14F9']), $this->output);
        } finally {
            $expected = <<<OUTPUT
Subscription Streak\StreakBundle\Tests\Command\RunSubscriptionCommandTest\SubscriptionId1(EC2BE294-C07A-4198-A159-4551686F14F9) processed    0 events in < 1 sec.
\r\e[2KSubscription Streak\StreakBundle\Tests\Command\RunSubscriptionCommandTest\SubscriptionId1(EC2BE294-C07A-4198-A159-4551686F14F9) processed    1 events in < 1 sec.
\r\e[2KSubscription Streak\StreakBundle\Tests\Command\RunSubscriptionCommandTest\SubscriptionId1(EC2BE294-C07A-4198-A159-4551686F14F9) processed    2 events in < 1 sec.
\r\e[2KSubscription Streak\StreakBundle\Tests\Command\RunSubscriptionCommandTest\SubscriptionId1(EC2BE294-C07A-4198-A159-4551686F14F9) processed    2 events in < 1 sec.
OUTPUT;
            $this->assertEquals($expected, $this->output->output);
        }
    }
}

namespace Streak\StreakBundle\Tests\Command\RunSubscriptionCommandTest;

use Streak\Domain\Event\Listener;
use Streak\Domain\Id\UUID;
use Symfony\Component\Console\Output\Output;

class SubscriptionId1 extends UUID implements Listener\Id
{
}

class TestOutput extends Output
{
    public $output = '';

    public function section() : self
    {
        return $this;
    }

    public function clear() : void
    {
    }

    protected function doWrite($message, $newline) : void
    {
        if ("\x1B[2K" === $message) {
            return;
        }

        $this->output .= $message.($newline ? PHP_EOL : '');
    }
}
