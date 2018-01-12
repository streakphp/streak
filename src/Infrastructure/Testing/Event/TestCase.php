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

namespace Streak\Infrastructure\Testing\Event;

use Streak\Domain\Event;
use Streak\Infrastructure\Testing\Message;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @codeCoverageIgnore
 */
abstract class TestCase extends Message\TestCase
{
    /**
     * @dataProvider provideExampleMessages
     */
    public function testConverting(Event $event)
    {
        parent::testConverting($event);
    }
}
