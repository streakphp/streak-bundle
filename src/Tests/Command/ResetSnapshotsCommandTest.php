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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Infrastructure\AggregateRoot\Snapshotter;
use Streak\Infrastructure\Resettable;
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
    /**
     * @var Snapshotter\Storage|Resettable|MockObject
     */
    private $resettablStorage;

    /**
     * @var Snapshotter\Storage|MockObject
     */
    private $nonResettableStorage;

    /**
     * @var OutputInterface|MockObject
     */
    private $output;

    protected function setUp() : void
    {
        $this->resettablStorage = $this->getMockBuilder(ResettableStorage::class)->getMock();
        $this->nonResettableStorage = $this->getMockBuilder(Snapshotter\Storage::class)->getMockForAbstractClass();
        $this->output = $this->getMockBuilder(OutputInterface::class)->getMockForAbstractClass();
    }

    public function testSuccessfulReset()
    {
        $command = new ResetSnapshotsCommand($this->resettablStorage);

        $this->resettablStorage
            ->expects($this->once())
            ->method('reset')
            ->with()
            ->willReturn(true)
        ;

        $this->resettablStorage
            ->expects($this->never())
            ->method('find')
        ;

        $this->resettablStorage
            ->expects($this->never())
            ->method('store')
        ;

        $this->output
            ->expects($this->once())
            ->method('writeln')
            ->with('<info>Snapshots storage reset succeeded.</info>')
        ;

        $command->run(new StringInput(''), $this->output);
    }

    public function testFailedReset()
    {
        $command = new ResetSnapshotsCommand($this->resettablStorage);

        $this->resettablStorage
            ->expects($this->once())
            ->method('reset')
            ->with()
            ->willReturn(false)
        ;

        $this->resettablStorage
            ->expects($this->never())
            ->method('find')
        ;

        $this->resettablStorage
            ->expects($this->never())
            ->method('store')
        ;

        $this->output
            ->expects($this->once())
            ->method('writeln')
            ->with('<error>Snapshots storage reset failed.</error>')
        ;

        $command->run(new StringInput(''), $this->output);
    }

    public function testUnsupportedReset()
    {
        $command = new ResetSnapshotsCommand($this->nonResettableStorage);

        $this->nonResettableStorage
            ->expects($this->never())
            ->method('find')
        ;

        $this->nonResettableStorage
            ->expects($this->never())
            ->method('store')
        ;

        $this->output
            ->expects($this->once())
            ->method('writeln')
            ->with('<comment>Reset functionality is not supported by current snapshots storage.</comment>')
        ;

        $command->run(new StringInput(''), $this->output);
    }
}

namespace Streak\StreakBundle\Tests\Command\ResetSnapshotsCommandTest;

use Streak\Infrastructure\AggregateRoot\Snapshotter\Storage;
use Streak\Infrastructure\Resettable;

abstract class ResettableStorage implements Storage, Resettable
{
}
