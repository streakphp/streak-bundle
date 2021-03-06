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

use Streak\Infrastructure\Domain\Event;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\StreakBundle\Tests\DependencyInjection\Compiler\RegisterListenerFactoriesCompilerPassTest
 */
class RegisterListenerFactoriesCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $composite = $container->findDefinition(Event\Listener\CompositeFactory::class);
        $factories = $container->findTaggedServiceIds('streak.listener_factory');

        foreach ($factories as $id => $tags) {
            $service = $container->findDefinition($id);

            // we skip decorators
            if (null !== $service->getDecoratedService()) {
                continue;
            }

            $composite->addMethodCall('add', [new Reference($id)]);
        }
    }
}
