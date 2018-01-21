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
    public function testException()
    {
        $message = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $previous = new \Exception();

        $exception = new ConversionToArrayNotPossible($message, $previous);

        $this->assertSame($message, $exception->event());
        $this->assertSame('Conversion not possible.', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
