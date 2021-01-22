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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Streak\Domain\Event;
use Streak\Infrastructure\Event\Converter\NestedObjectConverter;
use Streak\Infrastructure\Event\Subscription\DAO;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Event\Subscription\DAO\DbalPostgresDAO
 */
class DbalPostgresDAOTest extends DAOTestCase
{
    /**
     * @var Connection
     */
    private static $connection;

    public static function setUpBeforeClass()
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

    public function newDAO(Subscription\Factory $subscriptions, Event\Listener\Factory $listeners) : DAO
    {
        $dao = new DbalPostgresDAO(new Subscription\Factory($this->clock), $listeners, self::$connection, new NestedObjectConverter());
        $dao->drop();
        $dao->create();

        return $dao;
    }

    public function testDAO()
    {
        parent::testDAO();

        $listenerId1 = DAO\DAOTestCase\ListenerId::fromString('275b4f3e-ff07-4a48-8a24-d895c8d257b9');
        $listenerId3 = DAO\DAOTestCase\ListenerId::fromString('275b4f3e-ff07-4a48-8a24-d895c8d257b9');

        $listener3 = $this->getMockBuilder([Event\Listener::class, Event\Listener\Completable::class])->setMockClassName('listener3')->getMock();
        $listener3
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($listenerId3)
        ;
        $subscription3 = new Subscription($listener3, $this->clock);

        $all = $this->dao->all();
        $all = iterator_to_array($all);

        $this->assertNotEmpty($all);
        $this->assertTrue($this->dao->exists($listenerId1));
        $this->assertNotNull($this->dao->one($listenerId1));

        $this->dao->drop();

        $all = $this->dao->all();
        $all = iterator_to_array($all);

        $this->assertEmpty($all);

        $this->assertFalse($this->dao->exists($listenerId1));
        $this->assertNull($this->dao->one($listenerId1));

        $this->dao->save($subscription3);

        $subscription4 = $this->getMockBuilder([Event\Subscription::class, Event\Subscription\Decorator::class])->getMock();
        $subscription4->expects($this->any())->method('subscription')->willReturn($subscription3);

        $this->dao->save($subscription4);
    }
}
