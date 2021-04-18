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
use Streak\StreakBundle\DependencyInjection\Compiler\RegisterQueryHandlersCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\StreakBundle\DependencyInjection\Compiler\RegisterQueryHandlersCompilerPass
 */
class RegisterQueryHandlersCompilerPassTest extends TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();
        $container
            ->register('foo')
            ->addTag('streak.query_handler')
        ;
        $container
            ->register('bar')
            ->addTag('streak.query_handler')
        ;
        $container
            ->register('moo')
            ->addTag('streak.query_handler')
        ;
        $container
            ->register('nope')
        ;
        $container
            ->register('moo.decorator')
            ->addTag('streak.query_handler')
            ->setDecoratedService('moo')
        ;

        $composite = $container->register('streak.composite.query_handler');
        $this->process($container);

        self::assertTrue($this->handlerRegistered($composite, 'foo'));
        self::assertTrue($this->handlerRegistered($composite, 'bar'));
        self::assertTrue($this->handlerRegistered($composite, 'moo'));
        self::assertFalse($this->handlerRegistered($composite, 'nope'));
    }

    private function handlerRegistered(Definition $composite, $id): bool
    {
        $calls = $composite->getMethodCalls();
        $call = ['registerHandler', [new Reference($id)]];

        return false !== array_search($call, $calls);
    }

    private function process(ContainerBuilder $container)
    {
        (new RegisterQueryHandlersCompilerPass())->process($container);
    }
}
