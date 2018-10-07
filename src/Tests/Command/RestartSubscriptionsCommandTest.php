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
use Streak\Domain\Event;
use Streak\Domain\Event\Subscription\Exception;
use Streak\Domain\Event\Subscription\Repository;
use Streak\Infrastructure\UnitOfWork;
use Streak\StreakBundle\Command\RestartSubscriptionsCommand;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\StreakBundle\Command\RestartSubscriptionsCommand
 */
class RestartSubscriptionsCommandTest extends TestCase
{
    /**
     * @var Repository|MockObject
     */
    private $repository;

    /**
     * @var UnitOfWork
     */
    private $uow;

    /**
     * @var Event\Subscription|MockObject
     */
    private $subscription1;

    /**
     * @var Event\Subscription|MockObject
     */
    private $subscription2;

    /**
     * @var Event\Subscription|MockObject
     */
    private $subscription3;

    /**
     * @var Event\Subscription|MockObject
     */
    private $subscription4;

    /**
     * @var OutputInterface|MockObject
     */
    private $output;

    public function setUp()
    {
        $this->repository = $this->getMockBuilder(Repository::class)->getMockForAbstractClass();
        $this->uow = $this->getMockBuilder(UnitOfWork::class)->getMockForAbstractClass();

        $this->subscription1 = $this->getMockBuilder(Event\Subscription::class)->getMockForAbstractClass();
        $this->subscription2 = $this->getMockBuilder(Event\Subscription::class)->getMockForAbstractClass();
        $this->subscription3 = $this->getMockBuilder(Event\Subscription::class)->getMockForAbstractClass();
        $this->subscription4 = $this->getMockBuilder(Event\Subscription::class)->getMockForAbstractClass();

        $this->output = new ResetSubscriptionsCommandTest\TestOutput();
    }

    public function testWithoutCompletedSubscriptionsAtNormalVerbosity()
    {
        $this->repository
            ->expects($this->atLeastOnce())
            ->method('all')
            ->with(Repository\Filter::nothing())
            ->willReturn([$this->subscription1, $this->subscription2, $this->subscription3, $this->subscription4])
        ;

        $this->subscription1
            ->expects($this->once())
            ->method('restart')
        ;
        $this->subscription1
            ->expects($this->atLeastOnce())
            ->method('subscriptionId')
            ->willReturn(new ResetSubscriptionsCommandTest\SubscriptionId1('ec2be294-c07a-4198-a159-4551686f14f9'))
        ;

        $this->subscription2
            ->expects($this->once())
            ->method('restart')
            ->willThrowException(new Exception\SubscriptionRestartNotPossible($this->subscription2))
        ;
        $this->subscription2
            ->expects($this->atLeastOnce())
            ->method('subscriptionId')
            ->willReturn(new ResetSubscriptionsCommandTest\SubscriptionId2('71150509-d51a-4af1-b541-63c384709452'))
        ;

        $this->subscription3
            ->expects($this->once())
            ->method('restart')
            ->willThrowException(new \RuntimeException('test exception thrown.'))
        ;
        $this->subscription3
            ->expects($this->atLeastOnce())
            ->method('subscriptionId')
            ->willReturn(new ResetSubscriptionsCommandTest\SubscriptionId3('33532423-9d4c-42a8-8abc-7c6ef07face6'))
        ;

        $this->subscription4
            ->expects($this->once())
            ->method('restart')
        ;
        $this->subscription4
            ->expects($this->atLeastOnce())
            ->method('subscriptionId')
            ->willReturn(new ResetSubscriptionsCommandTest\SubscriptionId4('4894347a-dfaf-4ee0-83a6-39e650818507'))
        ;

        $this->output->setVerbosity($this->output::VERBOSITY_NORMAL);

        $command = new RestartSubscriptionsCommand($this->repository, $this->uow);
        $command->run(new StringInput(''), $this->output);

        $expected = <<<OUTPUT
Subscription Streak\StreakBundle\Tests\Command\ResetSubscriptionsCommandTest\SubscriptionId1(EC2BE294-C07A-4198-A159-4551686F14F9) restart succeeded.
Subscription Streak\StreakBundle\Tests\Command\ResetSubscriptionsCommandTest\SubscriptionId3(33532423-9D4C-42A8-8ABC-7C6EF07FACE6) restart failed with "test exception thrown.".
Subscription Streak\StreakBundle\Tests\Command\ResetSubscriptionsCommandTest\SubscriptionId4(4894347A-DFAF-4EE0-83A6-39E650818507) restart succeeded.

OUTPUT;
        $this->assertEquals($expected, $this->output->output);
    }

    public function testWithoutCompletedSubscriptionsAtVerboseVerbosity()
    {
        $this->repository
            ->expects($this->atLeastOnce())
            ->method('all')
            ->with(Repository\Filter::nothing())
            ->willReturn([$this->subscription1, $this->subscription2, $this->subscription3, $this->subscription4])
        ;

        $this->subscription1
            ->expects($this->once())
            ->method('restart')
        ;
        $this->subscription1
            ->expects($this->atLeastOnce())
            ->method('subscriptionId')
            ->willReturn(new ResetSubscriptionsCommandTest\SubscriptionId1('ec2be294-c07a-4198-a159-4551686f14f9'))
        ;

        $this->subscription2
            ->expects($this->once())
            ->method('restart')
            ->willThrowException(new Exception\SubscriptionRestartNotPossible($this->subscription2))
        ;
        $this->subscription2
            ->expects($this->atLeastOnce())
            ->method('subscriptionId')
            ->willReturn(new ResetSubscriptionsCommandTest\SubscriptionId2('71150509-d51a-4af1-b541-63c384709452'))
        ;

        $this->subscription3
            ->expects($this->once())
            ->method('restart')
            ->willThrowException(new \RuntimeException('test exception thrown.'))
        ;
        $this->subscription3
            ->expects($this->atLeastOnce())
            ->method('subscriptionId')
            ->willReturn(new ResetSubscriptionsCommandTest\SubscriptionId3('33532423-9d4c-42a8-8abc-7c6ef07face6'))
        ;

        $this->subscription4
            ->expects($this->once())
            ->method('restart')
        ;
        $this->subscription4
            ->expects($this->atLeastOnce())
            ->method('subscriptionId')
            ->willReturn(new ResetSubscriptionsCommandTest\SubscriptionId4('4894347a-dfaf-4ee0-83a6-39e650818507'))
        ;

        $this->output->setVerbosity($this->output::VERBOSITY_VERBOSE);

        $command = new RestartSubscriptionsCommand($this->repository, $this->uow);
        $command->run(new StringInput(''), $this->output);

        $expected = <<<OUTPUT
Subscription Streak\StreakBundle\Tests\Command\ResetSubscriptionsCommandTest\SubscriptionId1(EC2BE294-C07A-4198-A159-4551686F14F9) restart succeeded.
Subscription Streak\StreakBundle\Tests\Command\ResetSubscriptionsCommandTest\SubscriptionId2(71150509-D51A-4AF1-B541-63C384709452) restart not supported.
Subscription Streak\StreakBundle\Tests\Command\ResetSubscriptionsCommandTest\SubscriptionId3(33532423-9D4C-42A8-8ABC-7C6EF07FACE6) restart failed with "test exception thrown.".
Subscription Streak\StreakBundle\Tests\Command\ResetSubscriptionsCommandTest\SubscriptionId4(4894347A-DFAF-4EE0-83A6-39E650818507) restart succeeded.

OUTPUT;
        $this->assertEquals($expected, $this->output->output);
    }

    public function testWithCompletedSubscriptionsAtNormalVerbosity()
    {
        $this->repository
            ->expects($this->atLeastOnce())
            ->method('all')
            ->with(Repository\Filter::nothing()->doNotIgnoreCompletedSubscriptions())
            ->willReturn([$this->subscription1, $this->subscription2, $this->subscription3, $this->subscription4])
        ;

        $this->subscription1
            ->expects($this->once())
            ->method('restart')
        ;
        $this->subscription1
            ->expects($this->atLeastOnce())
            ->method('subscriptionId')
            ->willReturn(new ResetSubscriptionsCommandTest\SubscriptionId1('ec2be294-c07a-4198-a159-4551686f14f9'))
        ;

        $this->subscription2
            ->expects($this->once())
            ->method('restart')
            ->willThrowException(new Exception\SubscriptionRestartNotPossible($this->subscription2))
        ;
        $this->subscription2
            ->expects($this->atLeastOnce())
            ->method('subscriptionId')
            ->willReturn(new ResetSubscriptionsCommandTest\SubscriptionId2('71150509-d51a-4af1-b541-63c384709452'))
        ;

        $this->subscription3
            ->expects($this->once())
            ->method('restart')
            ->willThrowException(new \RuntimeException('test exception thrown.'))
        ;
        $this->subscription3
            ->expects($this->atLeastOnce())
            ->method('subscriptionId')
            ->willReturn(new ResetSubscriptionsCommandTest\SubscriptionId3('33532423-9d4c-42a8-8abc-7c6ef07face6'))
        ;

        $this->subscription4
            ->expects($this->once())
            ->method('restart')
        ;
        $this->subscription4
            ->expects($this->atLeastOnce())
            ->method('subscriptionId')
            ->willReturn(new ResetSubscriptionsCommandTest\SubscriptionId4('4894347a-dfaf-4ee0-83a6-39e650818507'))
        ;

        $this->output->setVerbosity($this->output::VERBOSITY_NORMAL);

        $command = new RestartSubscriptionsCommand($this->repository, $this->uow);
        $command->run(new StringInput('--include-completed'), $this->output);

        $expected = <<<OUTPUT
Subscription Streak\StreakBundle\Tests\Command\ResetSubscriptionsCommandTest\SubscriptionId1(EC2BE294-C07A-4198-A159-4551686F14F9) restart succeeded.
Subscription Streak\StreakBundle\Tests\Command\ResetSubscriptionsCommandTest\SubscriptionId3(33532423-9D4C-42A8-8ABC-7C6EF07FACE6) restart failed with "test exception thrown.".
Subscription Streak\StreakBundle\Tests\Command\ResetSubscriptionsCommandTest\SubscriptionId4(4894347A-DFAF-4EE0-83A6-39E650818507) restart succeeded.

OUTPUT;
        $this->assertEquals($expected, $this->output->output);
    }

    public function testWithCompletedSubscriptionsAtVerboseVerbosity()
    {
        $this->repository
            ->expects($this->atLeastOnce())
            ->method('all')
            ->with(Repository\Filter::nothing()->doNotIgnoreCompletedSubscriptions())
            ->willReturn([$this->subscription1, $this->subscription2, $this->subscription3, $this->subscription4])
        ;

        $this->subscription1
            ->expects($this->once())
            ->method('restart')
        ;
        $this->subscription1
            ->expects($this->atLeastOnce())
            ->method('subscriptionId')
            ->willReturn(new ResetSubscriptionsCommandTest\SubscriptionId1('ec2be294-c07a-4198-a159-4551686f14f9'))
        ;

        $this->subscription2
            ->expects($this->once())
            ->method('restart')
            ->willThrowException(new Exception\SubscriptionRestartNotPossible($this->subscription2))
        ;
        $this->subscription2
            ->expects($this->atLeastOnce())
            ->method('subscriptionId')
            ->willReturn(new ResetSubscriptionsCommandTest\SubscriptionId2('71150509-d51a-4af1-b541-63c384709452'))
        ;

        $this->subscription3
            ->expects($this->once())
            ->method('restart')
            ->willThrowException(new \RuntimeException('test exception thrown.'))
        ;
        $this->subscription3
            ->expects($this->atLeastOnce())
            ->method('subscriptionId')
            ->willReturn(new ResetSubscriptionsCommandTest\SubscriptionId3('33532423-9d4c-42a8-8abc-7c6ef07face6'))
        ;

        $this->subscription4
            ->expects($this->once())
            ->method('restart')
        ;
        $this->subscription4
            ->expects($this->atLeastOnce())
            ->method('subscriptionId')
            ->willReturn(new ResetSubscriptionsCommandTest\SubscriptionId4('4894347a-dfaf-4ee0-83a6-39e650818507'))
        ;

        $this->output->setVerbosity($this->output::VERBOSITY_VERBOSE);

        $command = new RestartSubscriptionsCommand($this->repository, $this->uow);
        $command->run(new StringInput('--include-completed'), $this->output);

        $expected = <<<OUTPUT
Subscription Streak\StreakBundle\Tests\Command\ResetSubscriptionsCommandTest\SubscriptionId1(EC2BE294-C07A-4198-A159-4551686F14F9) restart succeeded.
Subscription Streak\StreakBundle\Tests\Command\ResetSubscriptionsCommandTest\SubscriptionId2(71150509-D51A-4AF1-B541-63C384709452) restart not supported.
Subscription Streak\StreakBundle\Tests\Command\ResetSubscriptionsCommandTest\SubscriptionId3(33532423-9D4C-42A8-8ABC-7C6EF07FACE6) restart failed with "test exception thrown.".
Subscription Streak\StreakBundle\Tests\Command\ResetSubscriptionsCommandTest\SubscriptionId4(4894347A-DFAF-4EE0-83A6-39E650818507) restart succeeded.

OUTPUT;
        $this->assertEquals($expected, $this->output->output);
    }
}

namespace Streak\StreakBundle\Tests\Command\ResetSubscriptionsCommandTest;

use Streak\Domain\Id\UUID;
use Symfony\Component\Console\Output\Output;

class SubscriptionId1 extends UUID
{
}

class SubscriptionId2 extends UUID
{
}

class SubscriptionId3 extends UUID
{
}

class SubscriptionId4 extends UUID
{
}

class TestOutput extends Output
{
    public $output = '';

    protected function doWrite($message, $newline) : void
    {
        $this->output .= $message.($newline ? "\n" : '');
    }
}
