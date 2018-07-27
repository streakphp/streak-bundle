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
use Streak\Domain\Event\FilterableStream;
use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Sourced\Subscription;
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
    private $listeners;

    /**
     * @var EventStore|MockObject
     */
    private $store;

    /**
     * @var FilterableStream|MockObject
     */
    private $stream;

    /**
     * @var UnitOfWork
     */
    private $uow;

    /**
     * @var Listener|MockObject
     */
    private $listener1;

    /**
     * @var Listener|MockObject
     */
    private $listener2;

    /**
     * @var Listener|MockObject
     */
    private $listener3;

    /**
     * @var Subscription
     */
    private $subscription1;

    /**
     * @var Subscription
     */
    private $subscription2;

    /**
     * @var Subscription
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
     * @var OutputInterface|MockObject
     */
    private $output;

    public function setUp()
    {
        $this->listeners = $this->getMockBuilder(Repository::class)->getMockForAbstractClass();
        $this->store = $this->getMockBuilder(EventStore::class)->getMockForAbstractClass();
        $this->stream = $this->getMockBuilder(FilterableStream::class)->getMockForAbstractClass();
        $this->uow = new UnitOfWork($this->store);

        $this->listener1 = $this->getMockBuilder(Listener::class)->getMockForAbstractClass();
        $this->listener2 = $this->getMockBuilder(Listener::class)->getMockForAbstractClass();
        $this->listener3 = $this->getMockBuilder(Listener::class)->getMockForAbstractClass();

        $this->subscription1 = new Subscription($this->listener1);
        $this->subscription2 = new Subscription($this->listener2);
        $this->subscription3 = new Subscription($this->listener3);

        $this->event1 = $this->getMockBuilder(Event::class)->setMockClassName('event1')->getMockForAbstractClass();
        $this->event2 = $this->getMockBuilder(Event::class)->setMockClassName('event2')->getMockForAbstractClass();

        $this->subscription1->startFor($this->event1, new \DateTime());
        $this->subscription2->startFor($this->event1, new \DateTime());
        $this->subscription3->startFor($this->event1, new \DateTime());

        $this->output = $this->getMockBuilder(OutputInterface::class)->getMockForAbstractClass();
    }

    public function testCommand()
    {
        $command = new RunSubscriptionsCommand($this->listeners, $this->store, $this->uow);

        $this->listeners
            ->expects($this->atLeastOnce())
            ->method('all')
            ->with()
            ->willReturn([$this->subscription1, $this->subscription2, $this->subscription3])
        ;

        $this->store
            ->expects($this->exactly(3))
            ->method('stream')
            ->with()
            ->willReturnOnConsecutiveCalls(
                $this->stream,
                $this->stream,
                $this->stream
            )
        ;

        $this->isIteratorFor($this->stream, [$this->event2]);

        $this->listener1
            ->expects($this->once())
            ->method('on')
            ->with($this->event2)
        ;

        $exception = new \RuntimeException('Test exception.');

        $this->listener2
            ->expects($this->once())
            ->method('on')
            ->with($this->event2)
            ->willThrowException($exception)
        ;

        $this->listener3
            ->expects($this->once())
            ->method('on')
            ->with($this->event2)
        ;

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                ['Test exception.'],
                [$this->isType('string')]
            )
        ;

        $command->run(new StringInput(''), $this->output);
    }

    private function isIteratorFor(MockObject $iterator, array $items)
    {
        $internal = new \ArrayIterator($items);

        $iterator
            ->expects($this->any())
            ->method('rewind')
            ->willReturnCallback(function () use ($internal) {
                $internal->rewind();
            })
        ;

        $iterator
            ->expects($this->any())
            ->method('current')
            ->willReturnCallback(function () use ($internal) {
                return $internal->current();
            })
        ;

        $iterator
            ->expects($this->any())
            ->method('key')
            ->willReturnCallback(function () use ($internal) {
                return $internal->key();
            })
        ;

        $iterator
            ->expects($this->any())
            ->method('next')
            ->willReturnCallback(function () use ($internal) {
                $internal->next();
            })
        ;

        $iterator
            ->expects($this->any())
            ->method('valid')
            ->willReturnCallback(function () use ($internal) {
                return $internal->valid();
            })
        ;

        return $iterator;
    }
}
