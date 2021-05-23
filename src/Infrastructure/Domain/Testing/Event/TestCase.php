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

namespace Streak\Infrastructure\Domain\Testing\Event;

use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @codeCoverageIgnore
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    abstract public function provideExampleMessages(): array;

    /**
     * @dataProvider provideExampleMessages
     */
    public function testConverting(Event $message): void
    {
        $array = $this->createConverter()->objectToArray($message);
        $object = $this->createConverter()->arrayToObject($array);

        self::assertEquals($message, $object);
    }

    abstract protected function createConverter(): Event\Converter;
}
