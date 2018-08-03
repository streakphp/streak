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

namespace Streak\Infrastructure\EventStore;

use PHPUnit\Framework\MockObject\MockObject;
use Streak\Domain\EventStore;
use Streak\Domain\Exception\ConcurrentWriteDetected;
use Streak\Infrastructure\Event\Converter\FlatObjectConverter;
use Streak\Infrastructure\EventBus\EventStoreTestCase\Event1;
use Streak\Infrastructure\EventBus\EventStoreTestCase\ProducerId1;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\EventStore\PDOPostgresEventStore
 */
class PDOPostgresEventStoreTest extends EventStoreTestCase
{
    /**
     * @var \PDO
     */
    private static $pdo;

    public static function setUpBeforeClass()
    {
        self::$pdo = new \PDO($_ENV['DATABASE_URL']);
    }

    public function messages() : array
    {
        return [
            ["SQLSTATE[23505]: Unique violation: 7 BŁĄD:  podwójna wartość klucza narusza ograniczenie unikalności \"events_producer_type_producer_id_producer_version_key\"\nDETAIL:  Klucz (producer_type, producer_id, producer_version)=(Exchange\Domain\Exchange\Id, 3a4a3bfe-1e35-59f5-a95a-4390080d947b, 19) już istnieje."], // pl
            ["SQLSTATE[23505]: Unique violation: 7 ERROR: duplicate key value violates unique constraint \"events_producer_type_producer_id_producer_version_key\"\nDetail: Key (producer_type, producer_id, producer_version)=(Accounting\Domain\User\Id, A0B06313-3113-405A-89ED-319B97962D0C, 1) already exists."], // en
        ];
    }

    /**
     * @dataProvider messages
     */
    public function testEventAlreadyInStoreException($message)
    {
        /** @var \PDO|MockObject $pdo */
        $pdo = $this->getMockBuilder(\PDO::class)->disableOriginalConstructor()->getMock();
        /** @var \PDOStatement|MockObject $statement */
        $statement = $this->getMockBuilder(\PDOStatement::class)->getMock();
        $exception = new \PDOException($message, 23505);
        $store = new PDOPostgresEventStore($pdo, new FlatObjectConverter());

        $pdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($statement)
        ;

        $statement
            ->expects($this->once())
            ->method('execute')
            ->willThrowException($exception)
        ;

        $event1 = new Event1();
        $producer = new ProducerId1('producer1');

        $this->expectExceptionObject(new ConcurrentWriteDetected($producer));

        $store->add($producer, 0, $event1);
    }

    protected function newEventStore() : EventStore
    {
        $store = new PDOPostgresEventStore(self::$pdo, new FlatObjectConverter());
        $store->drop();
        $store->create();

        return $store;
    }
}
