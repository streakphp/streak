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

namespace Streak\Infrastructure\Event\Subscription\DAO;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\Event\Envelope;
use Streak\Domain\Event\Listener\Id;
use Streak\Domain\Id\UUID;
use Streak\Infrastructure\Event\Subscription\DAO;

/**
 * @covers \Streak\Infrastructure\Event\Subscription\DAO\IdentityMappingDao
 */
class IdentityMappingDaoTest extends TestCase
{
    /** @var DAO|MockObject */
    private $wrappedDaoMock;

    /** @var DAO */
    private $dao;

    public function setUp()
    {
        parent::setUp();
        $this->wrappedDaoMock = $this->getMockBuilder(DAO::class)->getMock();
        $this->dao = new IdentityMappingDao($this->wrappedDaoMock);
    }

    public function testItSavesNotSavedSubscription() : void
    {
        $this->wrappedDaoMock->expects($this->once())->method('save');
        $subscription = $this->createSubscriptionStub(
            'eea81580-4e00-4680-8f87-e96054d3c41b',
            'SubscriptionId',
            '3daa925f-6f9e-41b7-863d-8a3ffa12cca8'
        );

        $this->dao->save($subscription);
    }

    public function testItSaveSubscriptionWithoutLastProcessedEvent() : void
    {
        $this->wrappedDaoMock->expects($this->once())->method('save');
        $subscription = $this->createSubscriptionStub(
            'eea81580-4e00-4680-8f87-e96054d3c41b',
            'SubscriptionId',
            null
        );

        $this->dao->save($subscription);
    }

    public function testItSavesDifferentSubscriptions() : void
    {
        $subscriptionA = $this->createSubscriptionStub(
            'eea81580-4e00-4680-8f87-e96054d3c41b',
            'SubscriptionId',
            '3daa925f-6f9e-41b7-863d-8a3ffa12cca8'
        );

        $subscriptionB = $this->createSubscriptionStub(
            'a63a1d83-48a2-4de4-92c4-56a05099cdbf',
            'SubscriptionId',
            '3daa925f-6f9e-41b7-863d-8a3ffa12cca8'
        );

        $this->wrappedDaoMock->expects($this->exactly(2))->method('save');
        $this->dao->save($subscriptionA);
        $this->dao->save($subscriptionB);
    }

    public function testItSavesWhenSubscriptionChanged() : void
    {
        $subscription = $this->createSubscriptionStub(
            'eea81580-4e00-4680-8f87-e96054d3c41b',
            'SubscriptionId'
        );

        $lastProcessedEvent = $this->createEnvelopeStub('3daa925f-6f9e-41b7-863d-8a3ffa12cca8');
        $subscription->expects($this->any())->willReturnCallback(function () use (&$lastProcessedEvent) {
            return $lastProcessedEvent;
        })->method('lastProcessedEvent');

        $this->wrappedDaoMock->expects($this->exactly(2))->method('save');

        $this->dao->save($subscription);
        $lastProcessedEvent = $this->createEnvelopeStub('1646cc27-fbce-42e2-bff0-90c0a2e737cc');
        $this->dao->save($subscription);
    }

    public function testItDoesNotSaveTwice() : void
    {
        $subscription = $this->createSubscriptionStub(
            'eea81580-4e00-4680-8f87-e96054d3c41b',
            'SubscriptionId',
            '3daa925f-6f9e-41b7-863d-8a3ffa12cca8'
        );

        $this->wrappedDaoMock->expects($this->once())->method('save');

        $this->dao->save($subscription);
        $this->dao->save($subscription);
    }

    public function testItDoesNotSaveNotChangedSubscriptionGotByOne() : void
    {
        $this->wrappedDaoMock->expects($this->once())->method('one')->willReturn(
            $this->createSubscriptionStub('eea81580-4e00-4680-8f87-e96054d3c41b', 'SubscriptionId', '3daa925f-6f9e-41b7-863d-8a3ffa12cca8')
        );
        $this->wrappedDaoMock->expects($this->never())->method('save');

        $subscription = $this->dao->one($this->createSubscriptionIdStub('SubscriptionId', 'eea81580-4e00-4680-8f87-e96054d3c41b'));
        $this->dao->save($subscription);
    }

    public function testItDoesNotSaveNotChangedSubscriptionGotByOneWithoutLastProcessedEvent() : void
    {
        $this->wrappedDaoMock->expects($this->once())->method('one')->willReturn(
            $this->createSubscriptionStub('eea81580-4e00-4680-8f87-e96054d3c41b', 'SubscriptionId', null)
        );
        $this->wrappedDaoMock->expects($this->never())->method('save');

        $subscription = $this->dao->one($this->createSubscriptionIdStub('SubscriptionId', 'eea81580-4e00-4680-8f87-e96054d3c41b'));
        $this->dao->save($subscription);
    }

    public function testItDoesNotSaveNotChangedSubscriptionsGotByAll() : void
    {
        $subscriptions = [
            $this->createSubscriptionStub('eea81580-4e00-4680-8f87-e96054d3c41b', 'SubscriptionId', null),
            $this->createSubscriptionStub('d8daf5de-fff2-43c4-b30a-0595241ab1a4', 'SubscriptionId', '37352015-05b5-4e8e-b247-4246ecbddfe0'),
        ];
        $this->wrappedDaoMock
            ->expects($this->once())
            ->method('all')
            ->willReturnCallback(function () use ($subscriptions) : iterable {
                yield from $subscriptions;
            });
        $this->wrappedDaoMock->expects($this->never())->method('save');

        foreach ($this->dao->all() as $subscription) {
            $this->dao->save($subscription);
        }
    }

    public function testItReturnsAll() : void
    {
        $subscription = $this->createSubscriptionStub('eea81580-4e00-4680-8f87-e96054d3c41b', 'SubscriptionId');
        $this->wrappedDaoMock
            ->expects($this->once())
            ->method('all')
            ->willReturnCallback(function () use ($subscription) : iterable {
                yield $subscription;
            });
        self::assertEquals([$subscription], iterator_to_array($this->dao->all()));
    }

    public function testItReturnsOne() : void
    {
        $subscription = $this->createSubscriptionStub('eea81580-4e00-4680-8f87-e96054d3c41b', 'SubscriptionId');
        $this->wrappedDaoMock->expects($this->once())->method('one')->willReturn($subscription);
        self::assertSame($subscription, $this->dao->one($subscription->subscriptionId()));
    }

    public function testItExists() : void
    {
        $this->wrappedDaoMock->expects($this->once())->method('exists');
        $this->dao->exists($this->createSubscriptionIdStub('Id', 'be0e34c3-d53b-4463-b316-4a210971e64d'));
    }

    /**
     * @return Subscription|MockObject
     */
    private function createSubscriptionStub(
        string $subscriptionId,
        string $subscriptionIdClassName,
        string $lastProcessedEventId = null
    ) : Subscription {
        /** @var Subscription|MockObject $result */
        $result = $this->getMockBuilder(Subscription::class)
            ->disableOriginalConstructor()
            ->getMock();
        $result->expects($this->any())->method('subscriptionId')->willReturn($this->createSubscriptionIdStub($subscriptionIdClassName, $subscriptionId));
        if ($lastProcessedEventId) {
            $result->expects($this->any())->method('lastProcessedEvent')->willReturn($this->createEnvelopeStub($lastProcessedEventId));
        }

        return $result;
    }

    private function createEnvelopeStub(string $id) : Envelope
    {
        /** @var Event|MockObject $event */
        $event = $this->getMockBuilder(Event::class)->getMock();

        return new Envelope(new UUID($id), 'test', $event, new UUID($id));
    }

    private function createSubscriptionIdStub(string $className, string $id) : Id
    {
        /** @var Id|MockObject $result */
        $result = $this->getMockBuilder(Id::class)
            ->setMockClassName($className)
            ->getMock();
        $result->expects($this->any())->method('toString')->willReturn($id);

        return $result;
    }
}
