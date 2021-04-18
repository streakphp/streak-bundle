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
    private ContainerInterface $container;

    private UnitOfWork $uow;

    protected function setUp(): void
    {
        $this->container = $this->getMockBuilder(ContainerInterface::class)->getMockForAbstractClass();
        $this->uow = $this->getMockBuilder(UnitOfWork::class)->getMockForAbstractClass();
    }

    public function testObject()
    {
        $this->container
            ->expects(self::atLeastOnce())
            ->method('get')
            ->with('streak.composite.unit_of_work')
            ->willReturn($this->uow)
        ;

        $uow = new LazyLoadedUnitOfWork($this->container);

        $object = new \stdClass();

        $this->uow
            ->expects(self::once())
            ->method('add')
            ->with(self::identicalTo($object))
        ;

        $uow->add($object);

        $this->uow
            ->expects(self::exactly(2))
            ->method('has')
            ->with(self::identicalTo($object))
            ->willReturnOnConsecutiveCalls(true, false)
        ;

        self::assertTrue($uow->has($object));

        $this->uow
            ->expects(self::exactly(2))
            ->method('uncommitted')
            ->willReturnOnConsecutiveCalls([$object], [])
        ;

        self::assertSame([$object], $uow->uncommitted());

        $this->uow
            ->expects(self::exactly(2))
            ->method('count')
            ->willReturnOnConsecutiveCalls(1, 0)
        ;

        self::assertSame(1, $uow->count());

        $this->uow
            ->expects(self::once())
            ->method('remove')
            ->with(self::identicalTo($object))
        ;

        $uow->remove($object);

        self::assertFalse($uow->has($object));
        self::assertSame([], $uow->uncommitted());
        self::assertSame(0, $uow->count());

        $this->uow
            ->expects(self::once())
            ->method('clear')
        ;

        $uow->clear();

        $this->uow
            ->expects(self::once())
            ->method('commit')
            ->willReturnCallback(function () {
                yield from [];
            })
        ;

        $committed = $uow->commit();
        $committed = iterator_to_array($committed);

        self::assertEmpty($committed);
    }
}
