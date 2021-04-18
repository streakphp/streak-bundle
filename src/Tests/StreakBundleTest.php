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

namespace Streak\StreakBundle\Tests;

use PHPUnit\Framework\TestCase;
use Streak\StreakBundle\StreakBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\StreakBundle\StreakBundle
 */
class StreakBundleTest extends TestCase
{
    public function testBundle()
    {
        $bundle = new StreakBundle();
        $container = new ContainerBuilder();

        $config = $container->getCompilerPassConfig();
        $before = $config->getBeforeOptimizationPasses();

        $bundle->build($container);

        $after = $config->getBeforeOptimizationPasses();

        self::assertLessThan(\count($after), \count($before)); // compiler passes registered
    }
}
