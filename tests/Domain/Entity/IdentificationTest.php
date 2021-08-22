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

namespace Streak\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Streak\Domain\Entity;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Entity\Identification
 */
class IdentificationTest extends TestCase
{
    private Entity\Id $id;

    protected function setUp(): void
    {
        $this->id = $this->getMockBuilder(Entity\Id::class)->getMockForAbstractClass();
    }

    public function testObject(): void
    {
        $stub = new Entity\IdentificationTest\IdentifyingStub($this->id);
        self::assertSame($this->id, $stub->id());
    }
}

namespace Streak\Domain\Entity\IdentificationTest;

use Streak\Domain\Entity;

class IdentifyingStub
{
    use Entity\Identification;
}
