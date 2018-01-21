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

namespace Streak\Domain\Event\Sourced\Aggregate;

use PHPUnit\Framework\TestCase;
use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Sourced\Aggregate\Identification
 */
class IdentificationTest extends TestCase
{
    /**
     * @var Domain\Event\Sourced\Aggregate\Id|\PHPUnit_Framework_MockObject_MockObject
     */
    private $id;

    public function setUp()
    {
        $this->id = $this->getMockBuilder(Domain\Event\Sourced\Aggregate\Id::class)->getMockForAbstractClass();
    }

    public function testObject()
    {
        /* @var $identification Identification */
        $identification = $this->getMockBuilder(Identification::class)->setConstructorArgs([$this->id])->getMockForTrait();

        $this->assertSame($this->id, $identification->producerId());
        $this->assertSame($this->id, $identification->aggregateId());
        $this->assertSame($this->id, $identification->entityId());
        $this->assertSame($this->id, $identification->id());
    }
}
