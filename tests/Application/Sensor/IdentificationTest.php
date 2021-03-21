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

namespace Streak\Application\Sensor;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Application\Sensor;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Application\Sensor\Identification
 */
class IdentificationTest extends TestCase
{
    /**
     * @var Sensor\Id|MockObject
     */
    private $id;

    public function setUp() : void
    {
        $this->id = $this->getMockBuilder(Sensor\Id::class)->getMockForAbstractClass();
    }

    public function testObject()
    {
        /* @var $identification Identification */
        $identification = $this->getMockBuilder(Identification::class)->setConstructorArgs([$this->id])->getMockForTrait();

        $this->assertSame($this->id, $identification->sensorId());
        $this->assertSame($this->id, $identification->producerId());
        $this->assertSame($this->id, $identification->id());
    }
}
