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
use Streak\Domain\Event\Subscriber;
use Streak\StreakBundle\DependencyInjection\Compiler\CreateListenerSubscribersCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\StreakBundle\DependencyInjection\Compiler\CreateListenerSubscribersCompilerPass
 */
class CreateListenerSubscribersCompilerPassTest extends TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();
        $container
            ->register('foo')
            ->addTag('streak.listener_factory')
        ;
        $container
            ->register('bar')
            ->addTag('streak.listener_factory')
        ;
        $container
            ->register('moo')
            ->addTag('streak.listener_factory')
        ;
        $container
            ->register('nope')
        ;

        $this->process($container);

        $this->assertTrue($this->subscriberDefined('foo', $container));
        $this->assertTrue($this->subscriberDefined('bar', $container));
        $this->assertTrue($this->subscriberDefined('moo', $container));
        $this->assertFalse($this->subscriberDefined('nope', $container));
    }

    private function subscriberDefined(string $id, ContainerBuilder $container)
    {
        $id = $id.'.__streak_subscriber';

        if (false === $container->hasDefinition($id)) {
            return false;
        }

        $definition = $container->getDefinition($id);

        if (Subscriber::class !== $definition->getClass()) {
            return false;
        }

        if (false === $definition->hasTag('streak.listener_subscriber')) {
            return false;
        }

        return true;
    }

    private function process(ContainerBuilder $container)
    {
        (new CreateListenerSubscribersCompilerPass())->process($container);
    }
}
