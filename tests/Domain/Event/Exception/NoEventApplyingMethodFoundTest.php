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

namespace Streak\Domain\Event\Exception;

use PHPUnit\Framework\TestCase;
use Streak\Domain;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Exception\NoEventApplyingMethodFound
 */
class NoEventApplyingMethodFoundTest extends TestCase
{
    private Event\Sourced\Entity $entity;
    private Event\Envelope $event;

    protected function setUp(): void
    {
        $this->entity = $this->getMockBuilder(Event\Sourced\Entity::class)->getMockForAbstractClass();
        $this->event = Event\Envelope::new($this->getMockBuilder(Domain\Event::class)->getMockForAbstractClass(), $producerId = Domain\Id\UUID::random());
    }

    public function testException(): void
    {
        $exception = new NoEventApplyingMethodFound($this->entity, $this->event);

        self::assertSame($this->entity, $exception->object());
        self::assertSame($this->event, $exception->event());
    }
}
