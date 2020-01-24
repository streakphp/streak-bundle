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

use Streak\Infrastructure\UnitOfWork;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class LazyLoadedUnitOfWork implements UnitOfWork
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function add($object) : void
    {
        $this->container->get('streak.composite.unit_of_work')->add($object);
    }

    public function remove($object) : void
    {
        $this->container->get('streak.composite.unit_of_work')->remove($object);
    }

    public function has($object) : bool
    {
        return $this->container->get('streak.composite.unit_of_work')->has($object);
    }

    public function uncommitted() : array
    {
        return $this->container->get('streak.composite.unit_of_work')->uncommitted();
    }

    public function count() : int
    {
        return $this->container->get('streak.composite.unit_of_work')->count();
    }

    public function commit() : \Generator
    {
        yield from $this->container->get('streak.composite.unit_of_work')->commit();
    }

    public function clear() : void
    {
        $this->container->get('streak.composite.unit_of_work')->clear();
    }
}
