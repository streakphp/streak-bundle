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
use Streak\Domain\EventStore;
use Streak\Infrastructure\Domain\EventStore\Schema;
use Streak\StreakBundle\Command\CreateEventStoreSchemaCommand;
use Streak\StreakBundle\Tests\Command\CreateEventStoreSchemaCommandTest\EventStoreWithSchema;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\StreakBundle\Command\CreateEventStoreSchemaCommand
 */
class CreateEventStoreSchemaCommandTest extends TestCase
{
    private EventStoreWithSchema $schemableStore;

    private EventStore $schemalessStore;

    private Schema $schema;

    private OutputInterface $output;

    protected function setUp(): void
    {
        $this->schemableStore = $this->getMockBuilder(EventStoreWithSchema::class)->getMock();
        $this->schemalessStore = $this->getMockBuilder(EventStore::class)->getMockForAbstractClass();
        $this->schema = $this->getMockBuilder(Schema::class)->getMockForAbstractClass();
        $this->output = $this->getMockBuilder(OutputInterface::class)->getMockForAbstractClass();
    }

    public function testNotSchemable(): void
    {
        $command = new CreateEventStoreSchemaCommand($this->schemalessStore);

        $this->schemalessStore
            ->expects(self::never())
            ->method(self::anything())
        ;

        $this->schema
            ->expects(self::never())
            ->method(self::anything())
        ;

        $this->output
            ->expects(self::once())
            ->method('writeln')
            ->with("<comment>Can't create underlying event store's schema as functionality is unsupported.</comment>")
        ;

        $exit = $command->run(new StringInput(''), $this->output);

        self::assertSame(0, $exit);
    }

    public function testNoSchema(): void
    {
        $command = new CreateEventStoreSchemaCommand($this->schemableStore);

        $this->schemableStore
            ->expects(self::once())
            ->method('schema')
            ->with()
            ->willReturn(null)
        ;

        $this->schema
            ->expects(self::never())
            ->method(self::anything())
        ;

        $this->output
            ->expects(self::once())
            ->method('writeln')
            ->with("<comment>Can't create underlying event store's schema as functionality is unsupported.</comment>")
        ;

        $exit = $command->run(new StringInput(''), $this->output);

        self::assertSame(0, $exit);
    }

    public function testSuccess(): void
    {
        $command = new CreateEventStoreSchemaCommand($this->schemableStore);

        $this->schemableStore
            ->expects(self::once())
            ->method('schema')
            ->with()
            ->willReturn($this->schema)
        ;

        $this->schemableStore
            ->expects(self::never())
            ->method(self::logicalNot(self::equalTo('schema')))
        ;

        $this->schema
            ->expects(self::once())
            ->method('create')
        ;

        $this->schema
            ->expects(self::never())
            ->method('drop')
        ;

        $this->output
            ->expects(self::once())
            ->method('writeln')
            ->with('<info>Creating event store schema succeeded.</info>')
        ;

        $exit = $command->run(new StringInput(''), $this->output);

        self::assertSame(0, $exit);
    }

    public function testError(): void
    {
        $command = new CreateEventStoreSchemaCommand($this->schemableStore);

        $exception = new \RuntimeException('Reason of the failure.');

        $this->schemableStore
            ->expects(self::once())
            ->method('schema')
            ->with()
            ->willReturn($this->schema)
        ;

        $this->schemableStore
            ->expects(self::never())
            ->method(self::logicalNot(self::equalTo('schema')))
        ;

        $this->schema
            ->expects(self::once())
            ->method('create')
            ->with()
            ->willThrowException($exception)
        ;

        $this->schema
            ->expects(self::never())
            ->method('drop')
        ;

        $this->output
            ->expects(self::exactly(3))
            ->method('writeln')
            ->withConsecutive(
                ['<error>Creating event store schema failed.</error>'],
                ['<error>Reason:</error>'],
                ['<error>Reason of the failure.</error>']
            )
        ;

        $exit = $command->run(new StringInput(''), $this->output);

        self::assertNotSame(0, $exit);
    }
}

namespace Streak\StreakBundle\Tests\Command\CreateEventStoreSchemaCommandTest;

use Streak\Domain\EventStore;
use Streak\Infrastructure\Domain\EventStore\Schemable;

abstract class EventStoreWithSchema implements EventStore, Schemable
{
}
