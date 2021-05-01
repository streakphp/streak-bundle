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

use Streak\Infrastructure\Application\CommandBus\SynchronousCommandBus;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\StreakBundle\Tests\DependencyInjection\Compiler\RegisterCommandHandlersCompilerPassTest
 */
class RegisterCommandHandlersCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $bus = $container->findDefinition(SynchronousCommandBus::class);
        $factories = $container->findTaggedServiceIds('streak.command_handler');

        foreach ($factories as $id => $tags) {
            $service = $container->findDefinition($id);

            // we skip decorators
            if (null !== $service->getDecoratedService()) {
                continue;
            }

            $bus->addMethodCall('register', [new Reference($id)]);
        }
    }
}
