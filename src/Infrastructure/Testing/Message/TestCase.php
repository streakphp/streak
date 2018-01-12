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

namespace Streak\Infrastructure\Testing\Message;

use Streak\Domain\Message;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @codeCoverageIgnore
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    abstract public function provideExampleMessages() : array;

    /**
     * @dataProvider provideExampleMessages
     */
    public function testConverting(Message $message)
    {
        $array = $this->createConverter()->messageToArray($message);
        $object = $this->createConverter()->arrayToMessage(get_class($message), $array);

        $this->assertEquals($message, $object);
    }

    abstract protected function createConverter() : Message\Converter;
}
