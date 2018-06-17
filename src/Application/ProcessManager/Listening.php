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

namespace Streak\Application\ProcessManager;

use Streak\Application;
use Streak\Domain\Event;
use Streak\Infrastructure\CommandBus\NullCommandBus;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
trait Listening // implements Application\ProcessManager
{
    use Event\Listening {
        Event\Listening::on as private onEvent;
    }

    /**
     * @var Application\CommandBus
     */
    private $bus;

    public function __construct(Application\CommandBus $bus)
    {
        $this->dispatchCommandsVia($bus);
    }

    public function replay(Event\Stream $events) : void
    {
        if ($events->empty()) {
            return;
        }

        try {
            $backup = $this->bus;
            $this->bus = new NullCommandBus();
            foreach ($events as $event) {
                $this->onEvent($event);
            }
        } finally {
            $this->bus = $backup;
        }
    }

    private function dispatchCommandsVia(Application\CommandBus $bus) : void
    {
        $this->bus = $bus;
    }
}
