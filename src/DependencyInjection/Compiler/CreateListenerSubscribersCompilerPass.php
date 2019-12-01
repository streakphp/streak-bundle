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

use Streak\Domain\Event;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class CreateListenerSubscribersCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $factories = $container->findTaggedServiceIds('streak.listener_factory');

        foreach ($factories as $id => $tags) {
            $subscriber = new Definition(Event\Subscriber::class);
            $subscriber->setArguments([
                new Reference($id),
                new Reference('streak.subscription_factory'),
                new Reference('streak.subscription_repository'),
            ]);
            $subscriber->setPublic(false);
            $subscriber->addTag('streak.listener_subscriber');
            $subscriber->addMethodCall('listenTo', [new Reference('streak.event_bus')]);

            $container->setDefinition($id.'.__streak_subscriber', $subscriber);
        }
    }
}
