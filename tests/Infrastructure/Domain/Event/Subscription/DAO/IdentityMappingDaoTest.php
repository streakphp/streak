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

namespace Streak\Infrastructure\Domain\Event\Subscription\DAO;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event\Listener\Id;
use Streak\Infrastructure\Domain\Event\Subscription\DAO;

/**
 * @covers \Streak\Infrastructure\Domain\Event\Subscription\DAO\IdentityMappingDao
 */
class IdentityMappingDaoTest extends TestCase
{
    private DAO|MockObject $dao;

    protected function setUp(): void
    {
        $this->dao = $this->getMockBuilder(DAO::class)->getMock();
    }

    public function testItSavesNotSavedSubscription(): void
    {
        $this->dao->expects(self::once())->method('save');
        $subscription = $this->createSubscriptionStub(
            'eea81580-4e00-4680-8f87-e96054d3c41b',
            'SubscriptionId',
            0
        );

        $dao = new IdentityMappingDao($this->dao);
        $dao->save($subscription);
    }

    public function testItSavesDifferentSubscriptions(): void
    {
        $subscriptionA = $this->createSubscriptionStub(
            'eea81580-4e00-4680-8f87-e96054d3c41b',
            'SubscriptionId',
            1
        );

        $subscriptionB = $this->createSubscriptionStub(
            'a63a1d83-48a2-4de4-92c4-56a05099cdbf',
            'SubscriptionId',
            1
        );

        $this->dao->expects(self::exactly(2))->method('save');

        $dao = new IdentityMappingDao($this->dao);
        $dao->save($subscriptionA);
        $dao->save($subscriptionB);
    }

    public function testItSavesWhenSubscriptionChanged(): void
    {
        $subscription1 = $this->createSubscriptionStub(
            'eea81580-4e00-4680-8f87-e96054d3c41b',
            'SubscriptionId',
            1
        );
        $subscription2 = $this->createSubscriptionStub(
            'eea81580-4e00-4680-8f87-e96054d3c41b',
            'SubscriptionId',
            2
        );

        $this->dao->expects(self::exactly(2))->method('save');

        $dao = new IdentityMappingDao($this->dao);
        $dao->save($subscription1);
        $dao->save($subscription2);
    }

    public function testItDoesNotSaveTwice(): void
    {
        $subscription1 = $this->createSubscriptionStub(
            'eea81580-4e00-4680-8f87-e96054d3c41b',
            'SubscriptionId',
            0
        );
        $subscription2 = $this->createSubscriptionStub(
            'eea81580-4e00-4680-8f87-e96054d3c41b',
            'SubscriptionId',
            0
        );

        $this->dao->expects(self::once())->method('save');

        $dao = new IdentityMappingDao($this->dao);
        $dao->save($subscription1);
        $dao->save($subscription2);
    }

    public function testItDoesNotSaveNotChangedSubscriptionGotByOne(): void
    {
        $this->dao->expects(self::once())->method('one')->willReturn(
            $this->createSubscriptionStub('eea81580-4e00-4680-8f87-e96054d3c41b', 'SubscriptionId', 1)
        );
        $this->dao->expects(self::never())->method('save');

        $dao = new IdentityMappingDao($this->dao);
        $subscription = $dao->one($this->createSubscriptionIdStub('SubscriptionId', 'eea81580-4e00-4680-8f87-e96054d3c41b'));
        $dao->save($subscription);
    }

    public function testItDoesNotSaveNotChangedSubscriptionsGotByAll(): void
    {
        $subscriptions = [
            $this->createSubscriptionStub('eea81580-4e00-4680-8f87-e96054d3c41b', 'SubscriptionId', 123),
            $this->createSubscriptionStub('d8daf5de-fff2-43c4-b30a-0595241ab1a4', 'SubscriptionId', 234),
        ];
        $this->dao
            ->expects(self::once())
            ->method('all')
            ->willReturnCallback(function () use ($subscriptions): iterable {
                yield from $subscriptions;
            })
        ;
        $this->dao->expects(self::never())->method('save');

        $dao = new IdentityMappingDao($this->dao);
        foreach ($dao->all() as $subscription) {
            $dao->save($subscription);
        }
    }

    public function testItReturnsAll(): void
    {
        $subscription = $this->createSubscriptionStub('eea81580-4e00-4680-8f87-e96054d3c41b', 'SubscriptionId', 100);
        $this->dao
            ->expects(self::once())
            ->method('all')
            ->willReturnCallback(function () use ($subscription): iterable {
                yield $subscription;
            })
        ;
        $dao = new IdentityMappingDao($this->dao);
        self::assertEquals([$subscription], iterator_to_array($dao->all()));
    }

    public function testItReturnsOne(): void
    {
        $subscription = $this->createSubscriptionStub('eea81580-4e00-4680-8f87-e96054d3c41b', 'SubscriptionId', 100);
        $this->dao->expects(self::once())->method('one')->willReturn($subscription);
        $dao = new IdentityMappingDao($this->dao);
        self::assertSame($subscription, $dao->one($subscription->id()));
    }

    public function testItExists(): void
    {
        $this->dao->expects(self::once())->method('exists');
        $dao = new IdentityMappingDao($this->dao);
        $dao->exists($this->createSubscriptionIdStub('Id', 'be0e34c3-d53b-4463-b316-4a210971e64d'));
    }

    private function createSubscriptionStub(string $subscriptionId, string $subscriptionIdClassName, int $version): MockObject|Subscription
    {
        $result = $this->getMockBuilder(Subscription::class)->disableOriginalConstructor()->getMock();
        $result->method('id')->willReturn($this->createSubscriptionIdStub($subscriptionIdClassName, $subscriptionId));
        $result->method('version')->willReturn($version);

        return $result;
    }

    private function createSubscriptionIdStub(string $className, string $id): Id
    {
        $result = $this->getMockBuilder(Id::class)->setMockClassName($className)->getMock();
        $result->method('toString')->willReturn($id);

        return $result;
    }
}
