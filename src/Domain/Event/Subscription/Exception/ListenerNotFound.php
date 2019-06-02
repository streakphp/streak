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
 */
class ListenerNotFound extends \RuntimeException
{
    private $listenerId;

    public function __construct(Listener\Id $listenerId, \Throwable $previous = null)
    {
        $this->listenerId = $listenerId;

        $message = sprintf('Listener "%s@%s" not found.', get_class($this->listenerId), $this->listenerId->toString());

        parent::__construct($message, 0, $previous);
    }

    public function listenerId() : Listener\Id
    {
        return $this->listenerId;
    }
}
