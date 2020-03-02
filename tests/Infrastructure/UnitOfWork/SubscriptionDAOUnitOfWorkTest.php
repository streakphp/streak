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

namespace Streak\Infrastructure\UnitOfWork;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event\Listener\Id;
use Streak\Domain\Event\Subscription;
use Streak\Infrastructure\Event\Subscription\DAO;
use Streak\Infrastructure\UnitOfWork\Exception\ObjectNotSupported;

/**
 * @covers \Streak\Infrastructure\UnitOfWork\SubscriptionDAOUnitOfWork
 */
class SubscriptionDAOUnitOfWorkTest extends TestCase
{
    /** @var DAO */
    private $dao;

    /** @var SubscriptionDAOUnitOfWork */
    private $uow;

    public function setUp()
    {
        parent::setUp();
        $this->dao = $this->getMockBuilder(DAO::class)->getMockForAbstractClass();
        $this->uow = new SubscriptionDAOUnitOfWork($this->dao);
    }

    public function testItAdds() : void
    {
        $subscription = $this->createSubscriptionStub('1');
        $this->uow->add($subscription);
        self::assertTrue($this->uow->has($subscription));
    }

    public function testItAddsDecorator() : void
    {
        $decorator = $this->createSubscriptionDecoratorStub('1');
        $this->uow->add($decorator);
        self::assertTrue($this->uow->has($decorator));
    }

    public function testItDoesNotAddsNotSupportedSubscription() : void
    {
        $subscription = $this->getMockBuilder(Subscription::class)->getMock();
        self::expectException(ObjectNotSupported::class);
        $this->uow->add($subscription);
    }

    public function testHasOnNotSupportedObject() : void
    {
        $subscription = $this->getMockBuilder(Subscription::class)->getMock();
        self::expectException(ObjectNotSupported::class);
        $this->uow->has($subscription);
    }

    public function testItRemoves() : void
    {
        $subscription = $this->createSubscriptionStub('1');
        $this->uow->add($subscription);
        self::assertEquals(1, $this->uow->count());
        $this->uow->remove($subscription);
        self::assertFalse($this->uow->has($subscription));
        self::assertEmpty($this->uow->uncommitted());
        self::assertEquals(0, $this->uow->count());
    }

    public function testItRemovesNonExistedSubscription() : void
    {
        $subscription = $this->createSubscriptionStub('1');
        $this->uow->remove($subscription);
        self::assertFalse($this->uow->has($subscription));
        self::assertEmpty($this->uow->uncommitted());
    }

    public function testItDoesNotRemoveNotSupportedSubscription() : void
    {
        $subscription = $this->getMockBuilder(Subscription::class)->getMock();
        self::expectException(ObjectNotSupported::class);
        $this->uow->remove($subscription);
    }

    public function testItCommits() : void
    {
        $subscription = $this->createSubscriptionStub('1');
        $this->uow->add($subscription);
        $this->dao->expects($this->once())->method('save')->with($subscription);
        self::assertEquals([$subscription], $this->uow->uncommitted());
        iterator_to_array($this->uow->commit());
        self::assertEmpty($this->uow->uncommitted());
    }

    public function testItCommitsWithException() : void
    {
        $subscription = $this->createSubscriptionStub('1');
        $this->uow->add($subscription);
        $this->dao->expects($this->once())->method('save')->with($subscription)->willThrowException(new \Exception());
        self::expectException(\Exception::class);
        iterator_to_array($this->uow->commit());
        self::assertEquals([$subscription], $this->uow->uncommitted());
    }

    /**
     * @return Subscription\Decorator|MockObject
     */
    private function createSubscriptionDecoratorStub(string $id) : Subscription\Decorator
    {
        /** @var Subscription\Decorator|MockObject $result */
        $result = $this->getMockBuilder([Subscription\Decorator::class, Subscription::class])->getMock();
        $result->expects($this->any())->method('subscription')->willReturn($this->createSubscriptionStub($id));
        $result->expects($this->any())->method('subscriptionId')->willReturn($this->createIdStub($id));

        return $result;
    }

    /**
     * @return DAO\Subscription|MockObject
     */
    private function createSubscriptionStub(string $id) : DAO\Subscription
    {
        /** @var DAO\Subscription|MockObject $result */
        $result = $this->getMockBuilder(DAO\Subscription::class)->disableOriginalConstructor()->getMock();
        $result->expects($this->any())->method('subscriptionId')->willReturn($this->createIdStub($id));

        return $result;
    }

    private function createIdStub(string $id) : Id
    {
        /** @var Id|MockObject $result */
        $result = $this->getMockBuilder(Id::class)->getMock();
        $result->expects($this->any())->method('equals')->willReturnCallback(
            function ($argument) use ($id) {
                return $argument->toString() === $id;
            }
        );
        $result->expects($this->any())->method('toString')->willReturn($id);

        return $result;
    }
}
