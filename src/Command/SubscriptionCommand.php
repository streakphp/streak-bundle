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

namespace Streak\StreakBundle\Command;

use Streak\Domain\Event\Listener;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
abstract class SubscriptionCommand extends Command
{
    protected function formatSubscriptionId(Listener\Id $id): string
    {
        return sprintf('<fg=blue>%s</>(<fg=cyan>%s</>)', \get_class($id), $id->toString());
    }

    protected function subscriptionId(InputInterface $input): Listener\Id
    {
        $class = $input->getArgument('subscription-type');

        try {
            $reflection = new \ReflectionClass($class);
        } catch (\ReflectionException $exception) {
            throw new \InvalidArgumentException(sprintf('Given "subscription-type" argument "%s" is not a type of "%s"', $class, Listener\Id::class));
        }

        if (false === $reflection->implementsInterface(Listener\Id::class)) {
            throw new \InvalidArgumentException(sprintf('Given "subscription-type" argument "%s" is not a type of "%s"', $class, Listener\Id::class));
        }

        $id = (string) $input->getArgument('subscription-id');

        try {
            return $reflection->getMethod('fromString')->invoke(null, $id);
        } catch (\Throwable $exception) {
            throw new \InvalidArgumentException(sprintf('Given "subscription-id" argument "%s" is invalid', $id));
        }
    }
}
