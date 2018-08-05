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

use Streak\Infrastructure\AggregateRoot\Snapshotter;
use Streak\Infrastructure\Resettable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class ResetSnapshotsCommand extends Command
{
    private $storage;

    public function __construct(Snapshotter\Storage $storage)
    {
        $this->storage = $storage;

        parent::__construct(null);
    }

    protected function configure()
    {
        $this->setName('streak:snapshots:storage:reset');
        $this->setDescription('Removes all snapshots and resets related schemas');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->storage instanceof Resettable) {
            $output->writeln('<comment>Reset functionality is not supported by current snapshots storage.</comment>');

            return;
        }

        $result = $this->storage->reset();

        if (true === $result) {
            $output->writeln('<info>Snapshots storage reset succeeded.</info>');

            return;
        }

        $output->writeln('<error>Snapshots storage reset failed.</error>');
    }
}
