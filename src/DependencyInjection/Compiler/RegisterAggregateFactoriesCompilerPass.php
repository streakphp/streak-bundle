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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class RegisterAggregateFactoriesCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $composite = $container->findDefinition('streak.aggregate.factory.composite');
        $factories = $container->findTaggedServiceIds('streak.aggregate.factory');

        foreach ($factories as $id => $tags) {
            $composite->addMethodCall('add', [new Reference($id)]);
        }
    }
}