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

namespace Streak\Domain\AggregateRoot;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\AggregateRoot;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\AggregateRoot\Identification
 */
class IdentificationTest extends TestCase
{
    private AggregateRoot\Id|MockObject $id;

    protected function setUp(): void
    {
        $this->id = $this->getMockBuilder(AggregateRoot\Id::class)->getMockForAbstractClass();
    }

    public function testObject(): void
    {
        $stub = new AggregateRoot\IdentificationTest\IdentifyingStub($this->id);
        self::assertSame($this->id, $stub->id());
    }
}

namespace Streak\Domain\AggregateRoot\IdentificationTest;

use Streak\Domain\AggregateRoot;

class IdentifyingStub
{
    use AggregateRoot\Identification;
}
