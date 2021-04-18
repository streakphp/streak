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
use Streak\Domain\Sensor\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Exception\ConversionNotPossible
 * @covers \Streak\Domain\Event\Exception\ConversionToArrayNotPossible
 */
class ConversionToArrayNotPossibleTest extends TestCase
{
    public function testException(): void
    {
        $message = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $previous = new \Exception();

        $exception = new ConversionToArrayNotPossible($message, $previous);

        self::assertSame($message, $exception->object());
        self::assertSame('Conversion not possible.', $exception->getMessage());
        self::assertSame($previous, $exception->getPrevious());
    }
}
