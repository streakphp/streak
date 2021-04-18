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

use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
trait Replaying
{
    public function replay(Event\Stream $events): void
    {
        if ($events->empty()) {
            return;
        }

        try {
            $this->disableSideEffects();
            foreach ($events as $event) {
                $this->on($event);
            }
        } finally {
            $this->enableSideEffects();
        }
    }

    abstract public function on(Event\Envelope $event): bool;

    abstract protected function disableSideEffects(): void;

    abstract protected function enableSideEffects(): void;
}
