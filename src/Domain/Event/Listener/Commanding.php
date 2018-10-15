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

namespace Streak\Domain\Event\Listener;

use Streak\Application;
use Streak\Infrastructure\CommandBus\LockableCommandBus;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
trait Commanding
{
    /**
     * @var LockableCommandBus
     */
    private $bus;

    public function __construct(Application\CommandBus $bus)
    {
        $this->dispatchCommandsVia($bus);
    }

    private function muteCommands() : void
    {
        $this->bus->lock();
    }

    private function unmuteCommands() : void
    {
        $this->bus->unlock();
    }

    private function dispatchCommandsVia(Application\CommandBus $bus) : Application\CommandBus
    {
        $this->bus = new LockableCommandBus($bus);

        return $bus;
    }
}
