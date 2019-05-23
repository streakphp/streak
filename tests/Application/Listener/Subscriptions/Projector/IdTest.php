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

namespace Streak\Application\Listener\Subscriptions\Projector;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Streak\Application\Listener\Subscriptions\Projector\Id
 */
class IdTest extends TestCase
{
    public function testId()
    {
        $id1 = new Id();
        $id2 = new Id('e25e531b-1d6f-4881-b9d4-ad99268a3122');

        $this->assertSame('e25e531b-1d6f-4881-b9d4-ad99268a3122', $id1->toString());
        $this->assertSame('e25e531b-1d6f-4881-b9d4-ad99268a3122', $id2->toString());

        $this->assertTrue($id1->equals($id2));
        $this->assertTrue($id2->equals($id1));

        $id3 = Id::fromString('e25e531b-1d6f-4881-b9d4-ad99268a3122');

        $this->assertTrue($id1->equals($id3));
        $this->assertTrue($id2->equals($id3));
        $this->assertTrue($id3->equals($id1));
        $this->assertTrue($id3->equals($id2));

        $id4 = new \stdClass();

        $this->assertFalse($id1->equals($id4));
        $this->assertFalse($id2->equals($id4));
    }

    public function testWrongId()
    {
        $exception = new \InvalidArgumentException('Wrong UUID.');
        $this->expectExceptionObject($exception);

        new Id('wrong-id');
    }
}
