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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Streak\Domain\Event;
use Streak\Infrastructure\Domain\Event\Converter\NestedObjectConverter;
use Streak\Infrastructure\Domain\Event\Subscription\DAO;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\Event\Subscription\DAO\DbalPostgresDAO
 */
class DbalPostgresDAOTest extends DAOTestCase
{
    private static ?Connection $connection = null;

    public static function setUpBeforeClass(): void
    {
        self::$connection = DriverManager::getConnection([
            'driver' => 'pdo_pgsql',
            'host' => $_ENV['PHPUNIT_POSTGRES_HOSTNAME'],
            'port' => (int) $_ENV['PHPUNIT_POSTGRES_PORT'],
            'dbname' => $_ENV['PHPUNIT_POSTGRES_DATABASE'],
            'user' => $_ENV['PHPUNIT_POSTGRES_USERNAME'],
            'password' => $_ENV['PHPUNIT_POSTGRES_PASSWORD'],
        ]);
    }

    public function newDAO(Subscription\Factory $subscriptions, Event\Listener\Factory $listeners): DAO
    {
        $dao = new DbalPostgresDAO(new Subscription\Factory($this->clock), $listeners, self::$connection, new NestedObjectConverter());
        $dao->drop();
        $dao->create();

        return $dao;
    }

    public function testDAO(): void
    {
        parent::testDAO();

        $listenerId1 = DAO\DAOTestCase\ListenerId::fromString('275b4f3e-ff07-4a48-8a24-d895c8d257b9');
        $listenerId3 = DAO\DAOTestCase\ListenerId::fromString('275b4f3e-ff07-4a48-8a24-d895c8d257b9');

        $listener3 = $this->getMockBuilder(DAO\DbalPostgresDAOTest\CompletableListener::class)->setMockClassName('listener3')->getMock();
        $listener3
            ->expects(self::atLeastOnce())
            ->method('id')
            ->willReturn($listenerId3)
        ;
        $subscription3 = new Subscription($listener3, $this->clock);

        $all = $this->dao->all();
        $all = iterator_to_array($all);

        self::assertNotEmpty($all);
        self::assertTrue($this->dao->exists($listenerId1));
        self::assertNotNull($this->dao->one($listenerId1));

        $this->dao->drop();

        $all = $this->dao->all();
        $all = iterator_to_array($all);

        self::assertEmpty($all);

        self::assertFalse($this->dao->exists($listenerId1));
        self::assertNull($this->dao->one($listenerId1));

        $this->dao->save($subscription3);

        $subscription4 = $this->getMockBuilder(DAO\DbalPostgresDAOTest\DecoratedSubscription::class)->getMock();
        $subscription4->method('subscription')->willReturn($subscription3);

        $this->dao->save($subscription4);
    }
}

namespace Streak\Infrastructure\Domain\Event\Subscription\DAO\DbalPostgresDAOTest;

use Streak\Domain\Event;

abstract class DecoratedSubscription implements Event\Subscription, Event\Subscription\Decorator
{
}

abstract class CompletableListener implements Event\Listener, Event\Listener\Completable
{
}
