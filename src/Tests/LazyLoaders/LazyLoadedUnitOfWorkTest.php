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

namespace Streak\StreakBundle\Tests\LazyLoaders;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Infrastructure\UnitOfWork;
use Streak\StreakBundle\LazyLoaders\LazyLoadedUnitOfWork;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\StreakBundle\LazyLoaders\LazyLoadedUnitOfWork
 */
class LazyLoadedUnitOfWorkTest extends TestCase
{
    /**
     * @var ContainerInterface|MockObject
     */
    private $container;

    /**
     * @var UnitOfWork|MockObject
     */
    private $uow;

    protected function setUp()
    {
        $this->container = $this->getMockBuilder(ContainerInterface::class)->getMockForAbstractClass();
        $this->uow = $this->getMockBuilder(UnitOfWork::class)->getMockForAbstractClass();
    }

    public function testObject()
    {
        $this->container
            ->expects($this->atLeastOnce())
            ->method('get')
            ->with('streak.composite.unit_of_work')
            ->willReturn($this->uow)
        ;

        $uow = new LazyLoadedUnitOfWork($this->container);

        $object = new \stdClass();

        $this->uow
            ->expects($this->once())
            ->method('add')
            ->with($this->identicalTo($object))
        ;

        $uow->add($object);

        $this->uow
            ->expects($this->exactly(2))
            ->method('has')
            ->with($this->identicalTo($object))
            ->willReturnOnConsecutiveCalls(true, false)
        ;

        $this->assertTrue($uow->has($object));

        $this->uow
            ->expects($this->exactly(2))
            ->method('uncommitted')
            ->willReturnOnConsecutiveCalls([$object], [])
        ;

        $this->assertSame([$object], $uow->uncommitted());

        $this->uow
            ->expects($this->exactly(2))
            ->method('count')
            ->willReturnOnConsecutiveCalls(1, 0)
        ;

        $this->assertSame(1, $uow->count());

        $this->uow
            ->expects($this->once())
            ->method('remove')
            ->with($this->identicalTo($object))
        ;

        $uow->remove($object);

        $this->assertFalse($uow->has($object));
        $this->assertSame([], $uow->uncommitted());
        $this->assertSame(0, $uow->count());

        $this->uow
            ->expects($this->once())
            ->method('clear')
        ;

        $uow->clear();

        $this->uow
            ->expects($this->once())
            ->method('commit')
            ->willReturnCallback(function () { yield from []; })
        ;

        $committed = $uow->commit();
        $committed = iterator_to_array($committed);

        $this->assertEmpty($committed);
    }
}
