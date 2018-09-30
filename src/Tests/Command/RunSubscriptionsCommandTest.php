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
use Streak\Domain\Event\Stream;
use Streak\Domain\Event\Subscription\Repository;
use Streak\Domain\EventStore;
use Streak\Infrastructure\UnitOfWork;
use Streak\StreakBundle\Command\RunSubscriptionsCommand;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\StreakBundle\Command\RunSubscriptionsCommand
 */
class RunSubscriptionsCommandTest extends TestCase
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
     * @var Stream|MockObject
     */
    private $stream;

    /**
     * @var UnitOfWork|MockObject
     */
    private $uow;

    /**
     * @var Event\Subscription|MockObject
     */
    private $subscription1;

    /**
     * @var Event\Subscription|MockObject
     */
    private $subscription2;

    /**
     * @var Event\Subscription|MockObject
     */
    private $subscription3;

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
     * @var OutputInterface|MockObject
     */
    private $output;

    public function setUp()
    {
        $this->repository = $this->getMockBuilder(Repository::class)->getMockForAbstractClass();
        $this->store = $this->getMockBuilder(EventStore::class)->getMockForAbstractClass();
        $this->stream = $this->getMockBuilder([Stream::class, \IteratorAggregate::class])->getMock();
        $this->uow = $this->getMockBuilder(UnitOfWork::class)->getMockForAbstractClass();

        $this->subscription1 = $this->getMockBuilder([Event\Subscription::class, Event\Producer::class])->getMock();
        $this->subscription2 = $this->getMockBuilder([Event\Subscription::class, Event\Producer::class])->getMock();
        $this->subscription3 = $this->getMockBuilder([Event\Subscription::class, Event\Producer::class])->getMock();

        $this->event1 = $this->getMockBuilder(Event::class)->setMockClassName('event1')->getMockForAbstractClass();
        $this->event2 = $this->getMockBuilder(Event::class)->setMockClassName('event2')->getMockForAbstractClass();
        $this->event3 = $this->getMockBuilder(Event::class)->setMockClassName('event3')->getMockForAbstractClass();
        $this->event4 = $this->getMockBuilder(Event::class)->setMockClassName('event4')->getMockForAbstractClass();

        $this->output = new RunSubscriptionsCommandTest\TestOutput();
    }

    public function testCommand()
    {
        $command = new RunSubscriptionsCommand($this->repository, $this->store, $this->uow);

        $this->repository
            ->expects($this->atLeastOnce())
            ->method('all')
            ->with()
            ->willReturn([$this->subscription1, $this->subscription2, $this->subscription3])
        ;

        $this->subscription1
            ->expects($this->once())
            ->method('subscribeTo')
            ->with($this->store)
            ->willReturn([$this->event1, $this->event2])
        ;
        $this->subscription1
            ->expects($this->atLeastOnce())
            ->method('subscriptionId')
            ->willReturn(new RunSubscriptionsCommandTest\SubscriptionId1('ec2be294-c07a-4198-a159-4551686f14f9'))
        ;

        $this->subscription2
            ->expects($this->once())
            ->method('subscribeTo')
            ->with($this->store)
            ->willReturnCallback(function () {
                yield $this->event1;
                throw new \RuntimeException('test exception thrown.');
            })
        ;
        $this->subscription2
            ->expects($this->atLeastOnce())
            ->method('subscriptionId')
            ->willReturn(new RunSubscriptionsCommandTest\SubscriptionId2('71150509-d51a-4af1-b541-63c384709452'))
        ;

        $this->subscription3
            ->expects($this->once())
            ->method('subscribeTo')
            ->with($this->store)
            ->willReturn([$this->event1, $this->event2, $this->event3, $this->event4])
        ;
        $this->subscription3
            ->expects($this->atLeastOnce())
            ->method('subscriptionId')
            ->willReturn(new RunSubscriptionsCommandTest\SubscriptionId3('33532423-9d4c-42a8-8abc-7c6ef07face6'))
        ;

        $this->uow
            ->expects($this->exactly(9))
            ->method('commit')
        ;
        $this->uow
            ->expects($this->exactly(3))
            ->method('clear')
        ;

        $this->isIteratorFor($this->stream, [$this->event2]);

        $command->run(new StringInput(''), $this->output);

        $expected = <<<OUTPUT
Running Streak\StreakBundle\Tests\Command\RunSubscriptionsCommandTest\SubscriptionId1(EC2BE294-C07A-4198-A159-4551686F14F9) subscription:

    1 [->--------------------------]
    2 [-->-------------------------]

Running Streak\StreakBundle\Tests\Command\RunSubscriptionsCommandTest\SubscriptionId2(71150509-D51A-4AF1-B541-63C384709452) subscription:

    1 [->--------------------------]
Subscription Streak\StreakBundle\Tests\Command\RunSubscriptionsCommandTest\SubscriptionId2(71150509-D51A-4AF1-B541-63C384709452) failed with "test exception thrown.".


Running Streak\StreakBundle\Tests\Command\RunSubscriptionsCommandTest\SubscriptionId3(33532423-9D4C-42A8-8ABC-7C6EF07FACE6) subscription:

    1 [->--------------------------]
    2 [-->-------------------------]
    3 [--->------------------------]
    4 [---->-----------------------]


OUTPUT;
        $this->assertEquals($expected, $this->output->output);
    }

    private function isIteratorFor(MockObject $mock, array $items)
    {
        $iterator = new \ArrayIterator($items);

        $mock
            ->expects($this->any())
            ->method('getIterator')
            ->willReturn($iterator)
        ;

        return $mock;
    }
}

namespace Streak\StreakBundle\Tests\Command\RunSubscriptionsCommandTest;

use Streak\Domain\Id\UUID;
use Symfony\Component\Console\Output\Output;

class SubscriptionId1 extends UUID
{
}

class SubscriptionId2 extends UUID
{
}

class SubscriptionId3 extends UUID
{
}

class TestOutput extends Output
{
    public $output = '';

    protected function doWrite($message, $newline) : void
    {
        $this->output .= $message.($newline ? "\n" : '');
    }
}
