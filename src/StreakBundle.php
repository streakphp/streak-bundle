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

use Streak\Application\CommandHandler;
use Streak\Domain\AggregateRoot;
use Streak\Domain\Event\Listener;
use Streak\StreakBundle\DependencyInjection\Compiler\CreateListenerSubscribersCompilerPass;
use Streak\StreakBundle\DependencyInjection\Compiler\RegisterAggregateFactoriesCompilerPass;
use Streak\StreakBundle\DependencyInjection\Compiler\RegisterCommandHandlersCompilerPass;
use Streak\StreakBundle\DependencyInjection\Compiler\RegisterListenerFactoriesCompilerPass;
use Streak\StreakBundle\DependencyInjection\Compiler\RegisterListenerSubscribersCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class StreakBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->registerForAutoconfiguration(AggregateRoot\Factory::class)->addTag('streak.aggregate.factory');
        $container->registerForAutoconfiguration(CommandHandler::class)->addTag('streak.command_handler');
        $container->registerForAutoconfiguration(Listener\Factory::class)->addTag('streak.listener.factory');

        $container->addCompilerPass(new RegisterListenerFactoriesCompilerPass());
        $container->addCompilerPass(new CreateListenerSubscribersCompilerPass());
        $container->addCompilerPass(new RegisterListenerSubscribersCompilerPass());
        $container->addCompilerPass(new RegisterAggregateFactoriesCompilerPass());
        $container->addCompilerPass(new RegisterCommandHandlersCompilerPass());
    }
}
