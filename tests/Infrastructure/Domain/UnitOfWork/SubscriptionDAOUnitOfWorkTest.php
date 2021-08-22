<?php

/**
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Streak\Infrastructure\Domain\UnitOfWork;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event\Listener\Id;
use Streak\Domain\Event\Subscription;
use Streak\Infrastructure\Domain\Event\Subscription\DAO;
use Streak\Infrastructure\Domain\UnitOfWork\Exception\ObjectNotSupported;
use Streak\Infrastructure\Domain\UnitOfWork\SubscriptionDAOUnitOfWorkTest\DecoratedSubscription;

/**
 * @covers \Streak\Infrastructure\Domain\UnitOfWork\SubscriptionDAOUnitOfWork
 */
class SubscriptionDAOUnitOfWorkTest extends TestCase
{
    private DAO $dao;

    private SubscriptionDAOUnitOfWork $uow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao = $this->getMockBuilder(DAO::class)->getMockForAbstractClass();
        $this->uow = new SubscriptionDAOUnitOfWork($this->dao);
    }

    public function testItAdds(): void
    {
        $subscription = $this->createSubscriptionStub('1');
        $this->uow->add($subscription);
        self::assertTrue($this->uow->has($subscription));
    }

    public function testItAddsDecorator(): void
    {
        $decorator = $this->createSubscriptionDecoratorStub('1');
        $this->uow->add($decorator);
        self::assertTrue($this->uow->has($decorator));
    }

    public function testItDoesNotAddsNotSupportedSubscription(): void
    {
        $subscription = $this->getMockBuilder(Subscription::class)->getMock();
        $this->expectException(ObjectNotSupported::class);
        $this->uow->add($subscription);
    }

    public function testHasOnNotSupportedObject(): void
    {
        $subscription = $this->getMockBuilder(Subscription::class)->getMock();
        $this->expectException(ObjectNotSupported::class);
        $this->uow->has($subscription);
    }

    public function testItRemoves(): void
    {
        $subscription = $this->createSubscriptionStub('1');
        $this->uow->add($subscription);
        self::assertSame(1, $this->uow->count());
        $this->uow->remove($subscription);
        self::assertFalse($this->uow->has($subscription));
        self::assertEmpty($this->uow->uncommitted());
        self::assertSame(0, $this->uow->count());
    }

    public function testItRemovesNonExistedSubscription(): void
    {
        $subscription = $this->createSubscriptionStub('1');
        $this->uow->remove($subscription);
        self::assertFalse($this->uow->has($subscription));
        self::assertEmpty($this->uow->uncommitted());
    }

    public function testItDoesNotRemoveNotSupportedSubscription(): void
    {
        $subscription = $this->getMockBuilder(Subscription::class)->getMock();
        $this->expectException(ObjectNotSupported::class);
        $this->uow->remove($subscription);
    }

    public function testItCommits(): void
    {
        $subscription = $this->createSubscriptionStub('1');
        $this->uow->add($subscription);
        $this->dao->expects(self::once())->method('save')->with($subscription);
        self::assertEquals([$subscription], $this->uow->uncommitted());
        iterator_to_array($this->uow->commit());
        self::assertEmpty($this->uow->uncommitted());
    }

    public function testItCommitsWithException(): void
    {
        $subscription = $this->createSubscriptionStub('1');
        $this->uow->add($subscription);
        $this->dao->expects(self::once())->method('save')->with($subscription)->willThrowException(new \Exception());
        $this->expectException(\Exception::class);
        iterator_to_array($this->uow->commit());
        self::assertEquals([$subscription], $this->uow->uncommitted());
    }

    /**
     * @return MockObject|Subscription\Decorator
     */
    private function createSubscriptionDecoratorStub(string $id): Subscription\Decorator
    {
        $result = $this->getMockBuilder(DecoratedSubscription::class)->getMock();
        $result->method('subscription')->willReturn($this->createSubscriptionStub($id));
        $result->method('id')->willReturn($this->createIdStub($id));

        return $result;
    }

    /**
     * @return DAO\Subscription|MockObject
     */
    private function createSubscriptionStub(string $id): DAO\Subscription
    {
        $result = $this->getMockBuilder(DAO\Subscription::class)->disableOriginalConstructor()->getMock();
        $result->method('id')->willReturn($this->createIdStub($id));

        return $result;
    }

    private function createIdStub(string $id): Id
    {
        $result = $this->getMockBuilder(Id::class)->getMock();
        $result->method('equals')->willReturnCallback(
            fn ($argument) => $argument->toString() === $id
        );
        $result->method('toString')->willReturn($id);

        return $result;
    }
}

namespace Streak\Infrastructure\Domain\UnitOfWork\SubscriptionDAOUnitOfWorkTest;

use Streak\Domain\Event\Subscription;

abstract class DecoratedSubscription implements Subscription\Decorator, Subscription
{
}
