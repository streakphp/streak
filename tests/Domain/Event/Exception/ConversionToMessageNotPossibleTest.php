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

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Exception\ConversionNotPossible
 * @covers \Streak\Domain\Event\Exception\ConversionToEventNotPossible
 */
class ConversionToMessageNotPossibleTest extends TestCase
{
    public function testException()
    {
        $array = ['test' => 'array'];
        $previous = new \Exception();

        $exception = new ConversionToEventNotPossible($array, $previous);

        $this->assertSame($array, $exception->array());
        $this->assertSame('Conversion not possible.', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
