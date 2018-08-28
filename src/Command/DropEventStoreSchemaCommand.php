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
use Streak\Infrastructure\EventStore\Schemable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class DropEventStoreSchemaCommand extends Command
{
    private $store;

    public function __construct(EventStore $store)
    {
        $this->store = $store;

        parent::__construct(null);
    }

    protected function configure()
    {
        $this->setName('streak:event-store:schema:drop');
        $this->setDescription('Drops event store schema - for underlying persistence mechanism - if possible');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->store instanceof Schemable) {
            $output->writeln('<comment>Can\'t drop underlying event store\'s schema as functionality is unsupported.</comment>');

            return;
        }

        $schema = $this->store->schema();

        if (null === $schema) {
            $output->writeln('<comment>Can\'t drop underlying event store\'s schema as functionality is unsupported.</comment>');

            return;
        }

        try {
            $schema->drop();

            $output->writeln('<info>Dropping event store schema succeeded.</info>');
        } catch (\Throwable $e) {
            $output->writeln('<error>Dropping event store schema failed.</error>');
            $output->writeln('<error>Reason:</error>');
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return 1; // error
        }
    }
}
