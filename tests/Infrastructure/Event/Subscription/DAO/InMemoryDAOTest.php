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

use Streak\Domain\Event;
use Streak\Infrastructure\Event\Subscription\DAO;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Event\Subscription\DAO\InMemoryDAO
 */
class InMemoryDAOTest extends DAOTestCase
{
    public function newDAO(Subscription\Factory $subscriptions, Event\Listener\Factory $listeners): DAO
    {
        return new InMemoryDAO();
    }

    public function testDAO(): void
    {
        parent::testDAO();

        $all = $this->dao->all();
        $all = iterator_to_array($all);

        self::assertNotEmpty($all);

        $this->dao->clear();

        $all = $this->dao->all();
        $all = iterator_to_array($all);

        self::assertEmpty($all);
    }
}
