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

namespace Streak\StreakBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Streak\StreakBundle\DependencyInjection\Compiler\RegisterAggregateFactoriesCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\StreakBundle\DependencyInjection\Compiler\RegisterAggregateFactoriesCompilerPass
 */
class RegisterAggregateFactoriesCompilerPassTest extends TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();
        $container
            ->register('foo')
            ->addTag('streak.aggregate.factory')
        ;
        $container
            ->register('bar')
            ->addTag('streak.aggregate.factory')
        ;
        $container
            ->register('moo')
            ->addTag('streak.aggregate.factory')
        ;
        $container
            ->register('nope')
        ;

        $composite = $container->register('streak.aggregate.factory.composite');
        $this->process($container);

        $this->assertTrue($this->factoryRegistered($composite, 'foo'));
        $this->assertTrue($this->factoryRegistered($composite, 'bar'));
        $this->assertTrue($this->factoryRegistered($composite, 'moo'));
        $this->assertFalse($this->factoryRegistered($composite, 'nope'));
    }

    private function factoryRegistered(Definition $composite, $id)
    {
        $calls = $composite->getMethodCalls();
        $call = ['add', [new Reference($id)]];

        return false !== array_search($call, $calls);
    }

    private function process(ContainerBuilder $container)
    {
        (new RegisterAggregateFactoriesCompilerPass())->process($container);
    }
}
