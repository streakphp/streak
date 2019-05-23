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

namespace Streak\Application\Listener\Subscriptions\Projector\Query;

use PHPUnit\Framework\TestCase;
use Streak\Application\Listener\Subscriptions\Projector\Id;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Application\Listener\Subscriptions\Projector\Query\ListSubscriptions
 */
class ListSubscriptionsTest extends TestCase
{
    public function testQuery()
    {
        $query = new ListSubscriptions(null, null);

        $this->assertSame(null, $query->types());
        $this->assertSame(null, $query->completed());
        $this->assertEquals(new Id(), $query->listenerId());

        $query = new ListSubscriptions(null, true);

        $this->assertSame(null, $query->types());
        $this->assertSame(true, $query->completed());
        $this->assertEquals(new Id(), $query->listenerId());

        $query = new ListSubscriptions(null, false);

        $this->assertSame(null, $query->types());
        $this->assertSame(false, $query->completed());
        $this->assertEquals(new Id(), $query->listenerId());

        $query = new ListSubscriptions(['type' => 'uuid1'], null);

        $this->assertSame(['type' => 'uuid1'], $query->types());
        $this->assertSame(null, $query->completed());
        $this->assertEquals(new Id(), $query->listenerId());

        $query = new ListSubscriptions(['type' => 'uuid1'], true);

        $this->assertSame(['type' => 'uuid1'], $query->types());
        $this->assertSame(true, $query->completed());
        $this->assertEquals(new Id(), $query->listenerId());

        $query = new ListSubscriptions(['type' => 'uuid1'], false);

        $this->assertSame(['type' => 'uuid1'], $query->types());
        $this->assertSame(false, $query->completed());
        $this->assertEquals(new Id(), $query->listenerId());
    }
}
