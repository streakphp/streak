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

namespace Streak\Domain\Event\Listener;

use PHPUnit\Framework\TestCase;
use Streak\Domain;
use Streak\Domain\Event\Listener;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Listener\Identifying
 */
class IdentifyingTest extends TestCase
{
    /**
     * @var Domain\Id|\PHPUnit_Framework_MockObject_MockObject
     */
    private $id;

    public function setUp() : void
    {
        $this->id = $this->getMockBuilder(Listener\Id::class)->getMockForAbstractClass();
    }

    public function testObject()
    {
        /* @var $identification Identifying */
        $identification = $this->getMockBuilder(Identifying::class)->setConstructorArgs([$this->id])->getMockForTrait();

        $this->assertSame($this->id, $identification->id());
    }
}
