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

use Streak\Application\Saga;
use Streak\Domain;
use Streak\Domain\Message;
use Streak\Domain\Message\Exception;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class Subscriber implements Message\Subscriber
{
    private $listener;

    public function __construct(Saga\Listener $listener)
    {
        $this->listener = $listener;
    }

    /**
     * @throws Exception\InvalidMessageGiven
     */
    public function createFor(Domain\Message $message) : Message\Listener
    {
        if (!$this->listener->beginsWith($message)) {
            throw new Exception\InvalidMessageGiven($message);
        }

        return $this->listener;
    }
}
