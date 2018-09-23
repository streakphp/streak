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

namespace Streak\Infrastructure\Event;

use Streak\Domain;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class NullListener implements Event\Listener
{
    private $id;

    private function __construct(Domain\Id $id)
    {
        $this->id = $id;
    }

    public function id() : Domain\Id
    {
        return $this->id;
    }

    public function on(Event $event) : bool
    {
        return true;
    }

    public static function from(Event\Listener $listener)
    {
        return new self($listener->id());
    }
}
