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
    private Sensor\Id|MockObject $id;

    protected function setUp(): void
    {
        $this->id = $this->getMockBuilder(Sensor\Id::class)->getMockForAbstractClass();
    }

    public function testObject(): void
    {
        $stub = new Sensor\IdentificationTest\IdentifyingStub($this->id);
        self::assertSame($this->id, $stub->id());
    }
}

namespace Streak\Application\Sensor\IdentificationTest;

use Streak\Application\Sensor;

class IdentifyingStub
{
    use Sensor\Identification;
}
