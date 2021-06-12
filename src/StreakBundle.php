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

namespace Streak\StreakBundle;

use Streak\Application\Sensor;
use Streak\Domain\AggregateRoot;
use Streak\Domain\CommandHandler;
use Streak\Domain\Event\Listener;
use Streak\Domain\QueryHandler;
use Streak\StreakBundle\DependencyInjection\Compiler\CreateListenerSubscribersCompilerPass;
use Streak\StreakBundle\DependencyInjection\Compiler\RegisterAggregateFactoriesCompilerPass;
use Streak\StreakBundle\DependencyInjection\Compiler\RegisterCommandHandlersCompilerPass;
use Streak\StreakBundle\DependencyInjection\Compiler\RegisterListenerFactoriesCompilerPass;
use Streak\StreakBundle\DependencyInjection\Compiler\RegisterListenerSubscribersCompilerPass;
use Streak\StreakBundle\DependencyInjection\Compiler\RegisterQueryHandlersCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\StreakBundle\Tests\StreakBundleTest
 */
class StreakBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(AggregateRoot\Factory::class)->addTag('streak.aggregate_factory');
        $container->registerForAutoconfiguration(CommandHandler::class)->addTag('streak.command_handler');
        $container->registerForAutoconfiguration(QueryHandler::class)->addTag('streak.query_handler');
        $container->registerForAutoconfiguration(Listener\Factory::class)->addTag('streak.listener_factory');
        $container->registerForAutoconfiguration(Sensor\Factory::class)->addTag('streak.sensor_factory');

        $container->addCompilerPass(new RegisterListenerFactoriesCompilerPass());
        $container->addCompilerPass(new CreateListenerSubscribersCompilerPass());
        $container->addCompilerPass(new RegisterListenerSubscribersCompilerPass());
        $container->addCompilerPass(new RegisterAggregateFactoriesCompilerPass());
        $container->addCompilerPass(new RegisterCommandHandlersCompilerPass());
        $container->addCompilerPass(new RegisterQueryHandlersCompilerPass());
    }
}
