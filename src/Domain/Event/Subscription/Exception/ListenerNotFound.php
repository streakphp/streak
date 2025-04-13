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

namespace Streak\Domain\Event\Subscription\Exception;

use Streak\Domain\Event\Listener;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Domain\Event\Subscription\Exception\ListenerNotFoundTest
 */
class ListenerNotFound extends \RuntimeException
{
    public function __construct(private Listener\Id $listenerId, \Throwable $previous = null)
    {
        $message = \sprintf('Listener "%s@%s" not found.', $this->listenerId::class, $this->listenerId->toString());

        parent::__construct($message, 0, $previous);
    }

    public function listenerId(): Listener\Id
    {
        return $this->listenerId;
    }
}
