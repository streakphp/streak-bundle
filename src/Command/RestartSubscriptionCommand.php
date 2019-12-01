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
use Streak\Domain\Event\Subscription\Exception\SubscriptionRestartNotPossible;
use Streak\Domain\Event\Subscription\Repository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class RestartSubscriptionCommand extends Command
{
    private $subscriptions;

    public function __construct(Repository $subscriptions)
    {
        $this->subscriptions = $subscriptions;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('streak:subscription:restart');
        $this->setDescription('Restart single subscription');
        $this->setDefinition([
            new InputArgument('subscription-type', InputArgument::REQUIRED, 'Specify subscription type'),
            new InputArgument('subscription-id', InputArgument::REQUIRED, 'Specify subscription id'),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id = $this->id($input);
        $subscription = $this->subscriptions->find($id);

        if (null === $subscription) {
            $output->writeln(sprintf('Subscription %s not found.', $this->name($id)));

            return;
        }

        try {
            $subscription->restart();

            $output->writeln(sprintf('Subscription %s restart succeeded.', $this->name($id)));
        } catch (SubscriptionRestartNotPossible $exception) {
            $output->writeln(sprintf('Subscription %s restart not supported.', $this->name($id)));
        } catch (\Throwable $exception) {
            // lets write to stderr if possible
            if ($output instanceof ConsoleOutputInterface) {
                $output = $output->getErrorOutput(); // @codeCoverageIgnore
            }
            $output->writeln(sprintf('Subscription %s restart failed.', $this->name($id)));

            throw $exception;
        }
    }

    private function name(Listener\Id $id) : string
    {
        return sprintf('<fg=blue>%s</>(<fg=cyan>%s</>)', get_class($id), $id->toString());
    }

    private function id(InputInterface $input) : Listener\Id
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
