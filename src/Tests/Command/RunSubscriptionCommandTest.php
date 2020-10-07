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
use Streak\Domain\Id\UUID;
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
    public const TERMINAL_CLEAR_LINE = "\r\e[2K";

    /**
     * @var Repository|MockObject
     */
    private $repository;

    /**
     * @var EventStore|MockObject
     */
    private $store;

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
     * @var Event|MockObject
     */
    private $event3;

    /**
     * @var Event|MockObject
     */
    private $event4;

    /**
     * @var Event|MockObject
     */
    private $event5;

    /**
     * @var OutputInterface|MockObject
     */
    private $output;

    public function setUp()
    {
        $this->repository = $this->getMockBuilder(Repository::class)->getMockForAbstractClass();
        $this->store = $this->getMockBuilder(EventStore::class)->getMockForAbstractClass();

        $this->subscription1 = $this->getMockBuilder([Event\Subscription::class, Event\Producer::class])->getMock();

        $this->event1 = $this->getMockBuilder(Event::class)->setMockClassName('event1')->getMockForAbstractClass();
        $this->event2 = $this->getMockBuilder(Event::class)->setMockClassName('event2')->getMockForAbstractClass();
        $this->event3 = $this->getMockBuilder(Event::class)->setMockClassName('event3')->getMockForAbstractClass();
        $this->event4 = $this->getMockBuilder(Event::class)->setMockClassName('event4')->getMockForAbstractClass();
        $this->event5 = $this->getMockBuilder(Event::class)->setMockClassName('event5')->getMockForAbstractClass();

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
            ->with($this->store, null) // null is default --limit
            ->willReturn([$this->event1, $this->event2])
        ;

        $command = new RunSubscriptionCommand($this->repository, $this->store);
        $command->run(new ArrayInput(['subscription-type' => 'Streak\\StreakBundle\\Tests\\Command\\RunSubscriptionCommandTest\\SubscriptionId1', 'subscription-id' => 'EC2BE294-C07A-4198-A159-4551686F14F9']), $this->output);

        $expected =
            "Subscription Streak\StreakBundle\Tests\Command\RunSubscriptionCommandTest\SubscriptionId1(EC2BE294-C07A-4198-A159-4551686F14F9) processed    0 events in < 1 sec.".
            self::TERMINAL_CLEAR_LINE.
            "Subscription Streak\StreakBundle\Tests\Command\RunSubscriptionCommandTest\SubscriptionId1(EC2BE294-C07A-4198-A159-4551686F14F9) processed    2 events in < 1 sec."
        ;
        $this->assertEquals($expected, $this->output->output);
    }

    public function testCommandWithLimit()
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
            ->with($this->store, 763723)
            ->willReturn([$this->event1, $this->event2])
        ;

        $command = new RunSubscriptionCommand($this->repository, $this->store);
        $command->run(new ArrayInput(['subscription-type' => 'Streak\\StreakBundle\\Tests\\Command\\RunSubscriptionCommandTest\\SubscriptionId1', 'subscription-id' => 'EC2BE294-C07A-4198-A159-4551686F14F9', '--listening-limit' => 763723]), $this->output);

        $expected =
            "Subscription Streak\StreakBundle\Tests\Command\RunSubscriptionCommandTest\SubscriptionId1(EC2BE294-C07A-4198-A159-4551686F14F9) processed    0 events in < 1 sec.".
            self::TERMINAL_CLEAR_LINE.
            "Subscription Streak\StreakBundle\Tests\Command\RunSubscriptionCommandTest\SubscriptionId1(EC2BE294-C07A-4198-A159-4551686F14F9) processed    2 events in < 1 sec."
        ;
        $this->assertEquals($expected, $this->output->output);
    }

    public function testCommandWithInvalidType1()
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Given "subscription-type" argument "foo-bar" is not a type of "Streak\Domain\Event\Listener\Id"'));

        $command = new RunSubscriptionCommand($this->repository, $this->store);
        $command->run(new ArrayInput(['subscription-type' => 'foo-bar', 'subscription-id' => 'EC2BE294-C07A-4198-A159-4551686F14F9']), $this->output);
    }

    public function testCommandWithInvalidType2()
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Given "subscription-type" argument "Streak\Domain\Id\UUID" is not a type of "Streak\Domain\Event\Listener\Id"'));

        $command = new RunSubscriptionCommand($this->repository, $this->store);
        $command->run(new ArrayInput(['subscription-type' => UUID::class, 'subscription-id' => 'EC2BE294-C07A-4198-A159-4551686F14F9']), $this->output);
    }

    public function testCommandWithInvalidId()
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Given "subscription-id" argument "not-an-uuid" is invalid'));

        $command = new RunSubscriptionCommand($this->repository, $this->store);
        $command->run(new ArrayInput(['subscription-type' => 'Streak\\StreakBundle\\Tests\\Command\\RunSubscriptionCommandTest\\SubscriptionId1', 'subscription-id' => 'not-an-uuid']), $this->output);
    }

    public function testNotFound()
    {
        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(SubscriptionId1::fromString('EC2BE294-C07A-4198-A159-4551686F14F9'))
            ->willReturn(null)
        ;

        $command = new RunSubscriptionCommand($this->repository, $this->store);
        $command->run(new ArrayInput(['subscription-type' => 'Streak\\StreakBundle\\Tests\\Command\\RunSubscriptionCommandTest\\SubscriptionId1', 'subscription-id' => 'EC2BE294-C07A-4198-A159-4551686F14F9']), $this->output);

        $expected =
            "Subscription Streak\StreakBundle\Tests\Command\RunSubscriptionCommandTest\SubscriptionId1(EC2BE294-C07A-4198-A159-4551686F14F9) not found."
        ;
        $this->assertEquals($expected, $this->output->output);
    }

    public function testErrorWithoutPausing()
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

        $this->subscription1
            ->expects($this->never())
            ->method('pause')
        ;

        $this->expectExceptionObject(new \RuntimeException('Test exception thrown'));

        try {
            $command = new RunSubscriptionCommand($this->repository, $this->store);
            $command->run(new ArrayInput(['subscription-type' => 'Streak\\StreakBundle\\Tests\\Command\\RunSubscriptionCommandTest\\SubscriptionId1', 'subscription-id' => 'EC2BE294-C07A-4198-A159-4551686F14F9']), $this->output);
        } finally {
            $expected =
                "Subscription Streak\StreakBundle\Tests\Command\RunSubscriptionCommandTest\SubscriptionId1(EC2BE294-C07A-4198-A159-4551686F14F9) processed    0 events in < 1 sec.".
                self::TERMINAL_CLEAR_LINE.
                "Subscription Streak\StreakBundle\Tests\Command\RunSubscriptionCommandTest\SubscriptionId1(EC2BE294-C07A-4198-A159-4551686F14F9) processed    2 events in < 1 sec."
            ;
            $this->assertEquals($expected, $this->output->output);
        }
    }

    public function testErrorWithPausing()
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

        $this->subscription1
            ->expects($this->once())
            ->method('pause')
        ;

        $this->expectExceptionObject(new \RuntimeException('Test exception thrown'));

        try {
            $command = new RunSubscriptionCommand($this->repository, $this->store);
            $command->run(new ArrayInput(['--pause-on-error' => true, 'subscription-type' => 'Streak\\StreakBundle\\Tests\\Command\\RunSubscriptionCommandTest\\SubscriptionId1', 'subscription-id' => 'EC2BE294-C07A-4198-A159-4551686F14F9']), $this->output);
        } finally {
            $expected =
                "Subscription Streak\StreakBundle\Tests\Command\RunSubscriptionCommandTest\SubscriptionId1(EC2BE294-C07A-4198-A159-4551686F14F9) processed    0 events in < 1 sec.".
                self::TERMINAL_CLEAR_LINE.
                "Subscription Streak\StreakBundle\Tests\Command\RunSubscriptionCommandTest\SubscriptionId1(EC2BE294-C07A-4198-A159-4551686F14F9) processed    2 events in < 1 sec."
            ;
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
    public const TERMINAL_CLEAR_LINE = "\r\e[2K";

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
        $this->output .= $message;

        if (true === $newline) {
            $this->output .= PHP_EOL;
        }
    }
}
