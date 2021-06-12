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

use Streak\Domain\EventStore;
use Streak\Infrastructure\Domain\EventStore\Schemable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\StreakBundle\Tests\Command\CreateEventStoreSchemaCommandTest
 */
class CreateEventStoreSchemaCommand extends Command
{
    private EventStore $store;

    public function __construct(EventStore $store)
    {
        $this->store = $store;

        parent::__construct(null);
    }

    protected function configure(): void
    {
        $this->setName('streak:event-store:schema:create');
        $this->setDescription('Creates event store schema - for underlying persistence mechanism - if possible');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->store instanceof Schemable) {
            $output->writeln("<comment>Can't create underlying event store's schema as functionality is unsupported.</comment>");

            return 0;
        }

        $schema = $this->store->schema();

        if (null === $schema) {
            $output->writeln("<comment>Can't create underlying event store's schema as functionality is unsupported.</comment>");

            return 0;
        }

        try {
            $schema->create();

            $output->writeln('<info>Creating event store schema succeeded.</info>');
        } catch (\Throwable $e) {
            $output->writeln('<error>Creating event store schema failed.</error>');
            $output->writeln('<error>Reason:</error>');
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return 1; // error
        }

        return 0;
    }
}
