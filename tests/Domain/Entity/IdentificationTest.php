<?php

declare(strict_types=1);

/**
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Entity\Identification
 */
class IdentificationTest extends TestCase
{
    /**
     * @var Domain\Entity\Id|\PHPUnit_Framework_MockObject_MockObject
     */
    private $id;

    public function setUp()
    {
        $this->id = $this->getMockBuilder(Domain\Entity\Id::class)->getMockForAbstractClass();
    }

    public function testObject()
    {
        /* @var $identification Identification */
        $identification = $this->getMockBuilder(Identification::class)->setConstructorArgs([$this->id])->getMockForTrait();

        $this->assertSame($this->id, $identification->entityId());
        $this->assertSame($this->id, $identification->id());
    }
}
