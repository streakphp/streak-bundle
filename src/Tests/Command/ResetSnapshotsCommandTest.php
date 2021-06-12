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

namespace Streak\StreakBundle\Tests\Command;

use PHPUnit\Framework\TestCase;
use Streak\Infrastructure\Domain\AggregateRoot\Snapshotter;
use Streak\StreakBundle\Command\ResetSnapshotsCommand;
use Streak\StreakBundle\Tests\Command\ResetSnapshotsCommandTest\ResettableStorage;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\StreakBundle\Command\ResetSnapshotsCommand
 */
class ResetSnapshotsCommandTest extends TestCase
{
    private ResettableStorage $resettableStorage;

    private Snapshotter\Storage $nonResettableStorage;

    private OutputInterface $output;

    protected function setUp(): void
    {
        $this->resettableStorage = $this->getMockBuilder(ResettableStorage::class)->getMock();
        $this->nonResettableStorage = $this->getMockBuilder(Snapshotter\Storage::class)->getMockForAbstractClass();
        $this->output = $this->getMockBuilder(OutputInterface::class)->getMockForAbstractClass();
    }

    public function testSuccessfulReset(): void
    {
        $command = new ResetSnapshotsCommand($this->resettableStorage);

        $this->resettableStorage
            ->expects(self::once())
            ->method('reset')
            ->with()
            ->willReturn(true)
        ;

        $this->resettableStorage
            ->expects(self::never())
            ->method('find')
        ;

        $this->resettableStorage
            ->expects(self::never())
            ->method('store')
        ;

        $this->output
            ->expects(self::once())
            ->method('writeln')
            ->with('<info>Snapshots storage reset succeeded.</info>')
        ;

        $command->run(new StringInput(''), $this->output);
    }

    public function testFailedReset(): void
    {
        $command = new ResetSnapshotsCommand($this->resettableStorage);

        $this->resettableStorage
            ->expects(self::once())
            ->method('reset')
            ->with()
            ->willReturn(false)
        ;

        $this->resettableStorage
            ->expects(self::never())
            ->method('find')
        ;

        $this->resettableStorage
            ->expects(self::never())
            ->method('store')
        ;

        $this->output
            ->expects(self::once())
            ->method('writeln')
            ->with('<error>Snapshots storage reset failed.</error>')
        ;

        $command->run(new StringInput(''), $this->output);
    }

    public function testUnsupportedReset(): void
    {
        $command = new ResetSnapshotsCommand($this->nonResettableStorage);

        $this->nonResettableStorage
            ->expects(self::never())
            ->method('find')
        ;

        $this->nonResettableStorage
            ->expects(self::never())
            ->method('store')
        ;

        $this->output
            ->expects(self::once())
            ->method('writeln')
            ->with('<comment>Reset functionality is not supported by current snapshots storage.</comment>')
        ;

        $command->run(new StringInput(''), $this->output);
    }
}

namespace Streak\StreakBundle\Tests\Command\ResetSnapshotsCommandTest;

use Streak\Infrastructure\Domain\AggregateRoot\Snapshotter\Storage;
use Streak\Infrastructure\Domain\Resettable;

abstract class ResettableStorage implements Storage, Resettable
{
}
