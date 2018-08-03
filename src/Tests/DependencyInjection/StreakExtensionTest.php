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

namespace Streak\StreakBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Streak\StreakBundle\DependencyInjection\StreakExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\StreakBundle\DependencyInjection\StreakExtension
 */
class StreakExtensionTest extends TestCase
{
    public function testExtension()
    {
        $extension = new StreakExtension();

        $this->assertSame('streak', $extension->getAlias());
        $this->assertSame('http://streakphp.com/schema/dic/streak', $extension->getNamespace());

        return $extension;
    }

    /**
     * @depends testExtension
     */
    public function testLoad(StreakExtension $extension)
    {
        $container = new ContainerBuilder();

        $this->assertCount(1, $container->getDefinitions()); // container itself is registered

        $extension->load([], $container);

        $this->assertNotCount(1, $container->getDefinitions()); // more than before loading extension
    }
}