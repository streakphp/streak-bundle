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
use Streak\Infrastructure\Application\CommandBus\SynchronousCommandBus;
use Streak\StreakBundle\DependencyInjection\Compiler\RegisterCommandHandlersCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\StreakBundle\DependencyInjection\Compiler\RegisterCommandHandlersCompilerPass
 */
class RegisterCommandHandlersCompilerPassTest extends TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();
        $container
            ->register('foo')
            ->addTag('streak.command_handler')
        ;
        $container
            ->register('bar')
            ->addTag('streak.command_handler')
        ;
        $container
            ->register('moo')
            ->addTag('streak.command_handler')
        ;
        $container
            ->register('nope')
        ;
        $container
            ->register('moo.decorator')
            ->addTag('streak.command_handler')
            ->setDecoratedService('moo')
        ;

        $bus = $container->register(SynchronousCommandBus::class);
        $this->process($container);

        self::assertTrue($this->handlerRegistered($bus, 'foo'));
        self::assertTrue($this->handlerRegistered($bus, 'bar'));
        self::assertTrue($this->handlerRegistered($bus, 'moo'));
        self::assertFalse($this->handlerRegistered($bus, 'nope'));
    }

    private function handlerRegistered(Definition $composite, $id): bool
    {
        $calls = $composite->getMethodCalls();
        $call = ['register', [new Reference($id)]];

        return false !== array_search($call, $calls);
    }

    private function process(ContainerBuilder $container)
    {
        (new RegisterCommandHandlersCompilerPass())->process($container);
    }
}
