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

namespace Streak\Domain\Event;

use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Stream extends \Traversable
{
    public function first() : ?Event;

    public function last() : ?Event;

    public function empty() : bool;

    public function from(Event $event) : self;

    public function to(Event $event) : self;

    public function after(Event $event) : self;

    public function before(Event $event) : self;

    public function limit(int $limit) : self;

    public function only(string ...$types) : self;

    public function without(string ...$types) : self;
}
