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

namespace Streak\Application\Saga;

use Streak\Application\CommandBus;
use Streak\Application\Saga;
use Streak\Domain;
use Streak\Domain\Message;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class Listener implements Message\Listener, Message\Listener\Decorator, Message\Replayable
{
    private $bus;
    private $saga;

    public function __construct(Saga\Factory $factory, CommandBus $bus)
    {
        $this->bus = $bus;
        $this->saga = $factory->create();
    }

    public function decorated()
    {
        return $this->saga;
    }

    public function beginsWith(Domain\Message $message) : bool
    {
        return $this->saga->beginsWith($message);
    }

    public function on(Domain\Message $message) : void
    {
        $this->saga->on($message, $this->bus);
    }

    public function replay(Domain\Message ...$messages) : void
    {
        $this->saga->replay(...$messages);
    }
}
