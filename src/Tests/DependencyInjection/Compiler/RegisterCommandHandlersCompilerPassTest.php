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

        $composite = $container->register('streak.command_handler.composite');
        $this->process($container);

        $this->assertTrue($this->handlerRegistered($composite, 'foo'));
        $this->assertTrue($this->handlerRegistered($composite, 'bar'));
        $this->assertTrue($this->handlerRegistered($composite, 'moo'));
        $this->assertFalse($this->handlerRegistered($composite, 'nope'));
    }

    private function handlerRegistered(Definition $composite, $id) : bool
    {
        $calls = $composite->getMethodCalls();
        $call = ['registerHandler', [new Reference($id)]];

        return false !== array_search($call, $calls);
    }

    private function process(ContainerBuilder $container)
    {
        (new RegisterCommandHandlersCompilerPass())->process($container);
    }
}