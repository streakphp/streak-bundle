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
use Streak\Domain\EventStore;
use Streak\Infrastructure\EventStore\Schema;
use Streak\Infrastructure\EventStore\Schemable;
use Streak\StreakBundle\Command\DropEventStoreSchemaCommand;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\StreakBundle\Command\DropEventStoreSchemaCommand
 */
class DropEventStoreSchemaCommandTest extends TestCase
{
    /**
     * @var EventStore|Schemable|MockObject
     */
    private $schemableStore;

    /**
     * @var EventStore|MockObject
     */
    private $schemalessStore;

    /**
     * @var Schema|MockObject
     */
    private $schema;

    /**
     * @var OutputInterface|MockObject
     */
    private $output;

    protected function setUp()
    {
        $this->schemableStore = $this->getMockBuilder([EventStore::class, Schemable::class])->getMock();
        $this->schemalessStore = $this->getMockBuilder(EventStore::class)->getMockForAbstractClass();
        $this->schema = $this->getMockBuilder(Schema::class)->getMockForAbstractClass();
        $this->output = $this->getMockBuilder(OutputInterface::class)->getMockForAbstractClass();
    }

    public function testNotSchemable()
    {
        $command = new DropEventStoreSchemaCommand($this->schemalessStore);

        $this->schemalessStore
            ->expects($this->never())
            ->method($this->anything())
        ;

        $this->schema
            ->expects($this->never())
            ->method($this->anything())
        ;

        $this->output
            ->expects($this->once())
            ->method('writeln')
            ->with("<comment>Can't drop underlying event store's schema as functionality is unsupported.</comment>")
        ;

        $exit = $command->run(new StringInput(''), $this->output);

        $this->assertSame(0, $exit);
    }

    public function testNoSchema()
    {
        $command = new DropEventStoreSchemaCommand($this->schemableStore);

        $this->schemableStore
            ->expects($this->once())
            ->method('schema')
            ->with()
            ->willReturn(null)
        ;

        $this->schema
            ->expects($this->never())
            ->method($this->anything())
        ;

        $this->output
            ->expects($this->once())
            ->method('writeln')
            ->with("<comment>Can't drop underlying event store's schema as functionality is unsupported.</comment>")
        ;

        $exit = $command->run(new StringInput(''), $this->output);

        $this->assertSame(0, $exit);
    }

    public function testSuccess()
    {
        $command = new DropEventStoreSchemaCommand($this->schemableStore);

        $this->schemableStore
            ->expects($this->once())
            ->method('schema')
            ->with()
            ->willReturn($this->schema)
        ;

        $this->schemableStore
            ->expects($this->never())
            ->method($this->logicalNot($this->equalTo('schema')))
        ;

        $this->schema
            ->expects($this->once())
            ->method('drop')
        ;

        $this->schema
            ->expects($this->never())
            ->method('create')
        ;

        $this->output
            ->expects($this->once())
            ->method('writeln')
            ->with('<info>Dropping event store schema succeeded.</info>')
        ;

        $exit = $command->run(new StringInput(''), $this->output);

        $this->assertSame(0, $exit);
    }

    public function testError()
    {
        $command = new DropEventStoreSchemaCommand($this->schemableStore);

        $exception = new \RuntimeException('Reason of the failure.');

        $this->schemableStore
            ->expects($this->once())
            ->method('schema')
            ->with()
            ->willReturn($this->schema)
        ;

        $this->schemableStore
            ->expects($this->never())
            ->method($this->logicalNot($this->equalTo('schema')))
        ;

        $this->schema
            ->expects($this->once())
            ->method('drop')
            ->with()
            ->willThrowException($exception)
        ;

        $this->schema
            ->expects($this->never())
            ->method('create')
        ;

        $this->output
            ->expects($this->exactly(3))
            ->method('writeln')
            ->withConsecutive(
                ['<error>Dropping event store schema failed.</error>'],
                ['<error>Reason:</error>'],
                ['<error>Reason of the failure.</error>']
            )
        ;

        $exit = $command->run(new StringInput(''), $this->output);

        $this->assertNotSame(0, $exit);
    }
}
