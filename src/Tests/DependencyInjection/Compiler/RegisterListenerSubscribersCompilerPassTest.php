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
use Streak\Infrastructure\Domain\EventBus\InMemoryEventBus;
use Streak\StreakBundle\DependencyInjection\Compiler\RegisterListenerSubscribersCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\StreakBundle\DependencyInjection\Compiler\RegisterListenerSubscribersCompilerPass
 */
class RegisterListenerSubscribersCompilerPassTest extends TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();
        $container
            ->register('foo')
            ->addTag('streak.listener_subscriber')
        ;
        $container
            ->register('bar')
            ->addTag('streak.listener_subscriber')
        ;
        $container
            ->register('moo')
            ->addTag('streak.listener_subscriber')
        ;
        $container
            ->register('nope')
        ;

        $bus = $container->register(InMemoryEventBus::class);
        $this->process($container);

        self::assertTrue($this->subscriberRegistered($bus, 'foo'));
        self::assertTrue($this->subscriberRegistered($bus, 'bar'));
        self::assertTrue($this->subscriberRegistered($bus, 'moo'));
        self::assertFalse($this->subscriberRegistered($bus, 'nope'));
    }

    private function subscriberRegistered(Definition $bus, $id)
    {
        $calls = $bus->getMethodCalls();
        $call = ['add', [new Reference($id)]];

        return false !== array_search($call, $calls);
    }

    private function process(ContainerBuilder $container)
    {
        (new RegisterListenerSubscribersCompilerPass())->process($container);
    }
}
