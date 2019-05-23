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

namespace Streak\Infrastructure\Event\Subscription;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Application\Listener\Subscriptions\Projector\Query\ListSubscriptions;
use Streak\Application\QueryBus;
use Streak\Domain\Event;
use Streak\Domain\Event\Subscription\Repository;
use Streak\Infrastructure\Event\Subscription\ProjectionRepositoryTest\Id1;
use Streak\Infrastructure\Event\Subscription\ProjectionRepositoryTest\Id2;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Event\Subscription\ProjectionRepository
 */
class ProjectionRepositoryTest extends TestCase
{
    /**
     * @var Connection
     */
    private static $postgres;

    /**
     * @var QueryBus|MockObject
     */
    private $bus;

    /**
     * @var Repository|MockObject
     */
    private $repository;

    /**
     * @var Event\Subscription|MockObject
     */
    private $subscription1;

    /**
     * @var Event\Subscription|MockObject
     */
    private $subscription2;

    /**
     * @var Event\Listener\Id|MockObject
     */
    private $listenerId;

    public static function setUpBeforeClass()
    {
        self::$postgres = DriverManager::getConnection([
            'driver' => 'pdo_pgsql',
            'host' => $_ENV['PHPUNIT_POSTGRES_HOSTNAME'],
            'port' => (int) $_ENV['PHPUNIT_POSTGRES_PORT'],
            'dbname' => $_ENV['PHPUNIT_POSTGRES_DATABASE'],
            'user' => $_ENV['PHPUNIT_POSTGRES_USERNAME'],
            'password' => $_ENV['PHPUNIT_POSTGRES_PASSWORD'],
        ]);
    }

    protected function setUp()
    {
        $this->bus = $this->getMockBuilder(QueryBus::class)->getMockForAbstractClass();
        $this->repository = $this->getMockBuilder(Repository::class)->getMockForAbstractClass();
        $this->subscription1 = $this->getMockBuilder(Event\Subscription::class)->setMockClassName('subscription1')->getMockForAbstractClass();
        $this->subscription2 = $this->getMockBuilder(Event\Subscription::class)->setMockClassName('subscription2')->getMockForAbstractClass();
        $this->listenerId = $this->getMockBuilder(Event\Listener\Id::class)->getMockForAbstractClass();
    }

    public function testHas()
    {
        $repository = new ProjectionRepository($this->repository, $this->bus);

        $this->repository
            ->expects($this->exactly(2))
            ->method('has')
            ->with($this->subscription1)
            ->willReturnOnConsecutiveCalls(true, false)
        ;

        $has = $repository->has($this->subscription1);

        $this->assertTrue($has);

        $has = $repository->has($this->subscription1);

        $this->assertFalse($has);
    }

    public function testFind()
    {
        $this->repository
            ->expects($this->exactly(2))
            ->method('find')
            ->with($this->listenerId)
            ->willReturnOnConsecutiveCalls($this->subscription1, null)
        ;

        $repository = new ProjectionRepository($this->repository, $this->bus);
        $subscription = $repository->find($this->listenerId);

        $this->assertSame($this->subscription1, $subscription);
        $subscription = $repository->find($this->listenerId);

        $this->assertNull($subscription);
    }

    public function testAdd()
    {
        $repository = new ProjectionRepository($this->repository, $this->bus);

        $this->repository
            ->expects($this->once())
            ->method('add')
            ->with($this->subscription1)
        ;

        $repository->add($this->subscription1);
    }

    public function testAll1()
    {
        $repository = new ProjectionRepository($this->repository, $this->bus);

        $this->bus
            ->expects($this->once())
            ->method('dispatch')
            ->with(new ListSubscriptions())
            ->willReturn([])
        ;
        $this->repository
            ->expects($this->never())
            ->method('find')
        ;

        $subscriptions = $repository->all();
        $subscriptions = iterator_to_array($subscriptions);

        $this->assertSame([], $subscriptions);
    }

    public function testAll2()
    {
        $repository = new ProjectionRepository($this->repository, $this->bus);

        $this->bus
            ->expects($this->once())
            ->method('dispatch')
            ->with(new ListSubscriptions())
            ->willReturn([
                ['subscription_type' => Id1::class, 'subscription_id' => '58eec22a-c427-4744-841b-8a17059bbd2b'],
                ['subscription_type' => Id2::class, 'subscription_id' => '808469df-a572-4729-adc2-6e5ab6cd69d9'],
            ])
        ;
        $this->repository
            ->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive(
                [Id1::fromString('58eec22a-c427-4744-841b-8a17059bbd2b')],
                [Id2::fromString('808469df-a572-4729-adc2-6e5ab6cd69d9')]
            )
            ->willReturnOnConsecutiveCalls(
                $this->subscription1,
                $this->subscription2
            );

        $subscriptions = $repository->all(Repository\Filter::nothing());
        $subscriptions = iterator_to_array($subscriptions);

        $this->assertSame([$this->subscription1, $this->subscription2], $subscriptions);
    }

    public function testAll3()
    {
        $repository = new ProjectionRepository($this->repository, $this->bus);

        $this->bus
            ->expects($this->once())
            ->method('dispatch')
            ->with(new ListSubscriptions([Id1::class, Id2::class], false))
            ->willReturn([
                ['subscription_type' => Id1::class, 'subscription_id' => '58eec22a-c427-4744-841b-8a17059bbd2b'],
                ['subscription_type' => Id2::class, 'subscription_id' => '808469df-a572-4729-adc2-6e5ab6cd69d9'],
            ])
        ;
        $this->repository
            ->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive(
                [Id1::fromString('58eec22a-c427-4744-841b-8a17059bbd2b')],
                [Id2::fromString('808469df-a572-4729-adc2-6e5ab6cd69d9')]
            )
            ->willReturnOnConsecutiveCalls(
                $this->subscription1,
                $this->subscription2
            );

        $subscriptions = $repository->all(Repository\Filter::nothing()->filterSubscriptionTypes(Id1::class, Id2::class)->ignoreCompletedSubscriptions());
        $subscriptions = iterator_to_array($subscriptions);

        $this->assertSame([$this->subscription1, $this->subscription2], $subscriptions);
    }

    public function testAll4()
    {
        $repository = new ProjectionRepository($this->repository, $this->bus);

        $this->bus
            ->expects($this->once())
            ->method('dispatch')
            ->with(new ListSubscriptions([Id1::class, Id2::class], null))
            ->willReturn([
                ['subscription_type' => Id1::class, 'subscription_id' => '58eec22a-c427-4744-841b-8a17059bbd2b'],
                ['subscription_type' => Id2::class, 'subscription_id' => '808469df-a572-4729-adc2-6e5ab6cd69d9'],
            ])
        ;
        $this->repository
            ->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive(
                [Id1::fromString('58eec22a-c427-4744-841b-8a17059bbd2b')],
                [Id2::fromString('808469df-a572-4729-adc2-6e5ab6cd69d9')]
            )
            ->willReturnOnConsecutiveCalls(
                $this->subscription1,
                $this->subscription2
            );

        $subscriptions = $repository->all(Repository\Filter::nothing()->filterSubscriptionTypes(Id1::class, Id2::class)->doNotIgnoreCompletedSubscriptions());
        $subscriptions = iterator_to_array($subscriptions);

        $this->assertSame([$this->subscription1, $this->subscription2], $subscriptions);
    }

    public function testAll5()
    {
        $repository = new ProjectionRepository($this->repository, $this->bus);

        $this->bus
            ->expects($this->once())
            ->method('dispatch')
            ->with(new ListSubscriptions([Id1::class, Id2::class], false))
            ->willReturn([
                ['subscription_type' => Id1::class, 'subscription_id' => '58eec22a-c427-4744-841b-8a17059bbd2b'],
                ['subscription_type' => Id2::class, 'subscription_id' => '808469df-a572-4729-adc2-6e5ab6cd69d9'],
            ])
        ;
        $this->repository
            ->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive(
                [Id1::fromString('58eec22a-c427-4744-841b-8a17059bbd2b')],
                [Id2::fromString('808469df-a572-4729-adc2-6e5ab6cd69d9')]
            )
            ->willReturnOnConsecutiveCalls(
                null,
                $this->subscription2
            )
        ;

        $this->subscription2
            ->expects($this->once())
            ->method('completed')
            ->willReturn(true)
        ;

        $subscriptions = $repository->all(Repository\Filter::nothing()->filterSubscriptionTypes(Id1::class, Id2::class)->ignoreCompletedSubscriptions());
        $subscriptions = iterator_to_array($subscriptions);

        $this->assertSame([], $subscriptions);
    }

    public function testAll6()
    {
        $repository = new ProjectionRepository($this->repository, $this->bus);

        $this->bus
            ->expects($this->once())
            ->method('dispatch')
            ->with(new ListSubscriptions([Id1::class, Id2::class], false))
            ->willThrowException(new \RuntimeException())
        ;

        $this->repository
            ->expects($this->never())
            ->method('find')
        ;
        $this->repository
            ->expects($this->once())
            ->method('all')
            ->with($this->isInstanceOf(Repository\Filter::class))
            ->willReturn([$this->subscription1, $this->subscription2])
        ;

        $subscriptions = $repository->all(Repository\Filter::nothing()->filterSubscriptionTypes(Id1::class, Id2::class)->ignoreCompletedSubscriptions());
        $subscriptions = iterator_to_array($subscriptions);

        $this->assertSame([$this->subscription1, $this->subscription2], $subscriptions);
    }
}

namespace Streak\Infrastructure\Event\Subscription\ProjectionRepositoryTest;

use Streak\Domain\Event\Listener;
use Streak\Domain\Id\UUID;

class Id1 extends UUID implements Listener\Id
{
}
class Id2 extends UUID implements Listener\Id
{
}
