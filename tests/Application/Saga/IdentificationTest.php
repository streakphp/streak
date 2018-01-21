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

namespace Streak\Application\Saga;

use PHPUnit\Framework\TestCase;
use Streak\Application\Saga;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Application\Saga\Identification
 */
class IdentificationTest extends TestCase
{
    /**
     * @var Saga\Id|\PHPUnit_Framework_MockObject_MockObject
     */
    private $id;

    public function setUp()
    {
        $this->id = $this->getMockBuilder(Saga\Id::class)->getMockForAbstractClass();
    }

    public function testObject()
    {
        /* @var $identification Identification */
        $identification = $this->getMockBuilder(Identification::class)->setConstructorArgs([$this->id])->getMockForTrait();

        $this->assertSame($this->id, $identification->sagaId());
        $this->assertSame($this->id, $identification->id());
    }
}
