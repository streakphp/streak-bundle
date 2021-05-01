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

namespace Streak\StreakBundle\LazyLoaders;

use Streak\Infrastructure\Domain\UnitOfWork;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\StreakBundle\Tests\LazyLoaders\LazyLoadedUnitOfWorkTest
 */
class LazyLoadedUnitOfWork implements UnitOfWork
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function add($object): void
    {
        $this->container->get(UnitOfWork\CompositeUnitOfWork::class)->add($object);
    }

    public function remove($object): void
    {
        $this->container->get(UnitOfWork\CompositeUnitOfWork::class)->remove($object);
    }

    public function has($object): bool
    {
        return $this->container->get(UnitOfWork\CompositeUnitOfWork::class)->has($object);
    }

    public function uncommitted(): array
    {
        return $this->container->get(UnitOfWork\CompositeUnitOfWork::class)->uncommitted();
    }

    public function count(): int
    {
        return $this->container->get(UnitOfWork\CompositeUnitOfWork::class)->count();
    }

    public function commit(): \Generator
    {
        yield from $this->container->get(UnitOfWork\CompositeUnitOfWork::class)->commit();
    }

    public function clear(): void
    {
        $this->container->get(UnitOfWork\CompositeUnitOfWork::class)->clear();
    }
}
