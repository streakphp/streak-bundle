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

namespace Streak\StreakBundle\DependencyInjection\Compiler;

use Streak\Infrastructure\Domain\EventBus\InMemoryEventBus;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\StreakBundle\Tests\DependencyInjection\Compiler\RegisterListenerSubscribersCompilerPassTest
 */
class RegisterListenerSubscribersCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $composite = $container->findDefinition(InMemoryEventBus::class);
        $factories = $container->findTaggedServiceIds('streak.listener_subscriber');

        foreach ($factories as $id => $tags) {
            $composite->addMethodCall('add', [new Reference($id)]);
        }
    }
}
