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

namespace Streak\Application\Listener\Subscriptions\Projector\Query;

use Streak\Application\Listener\Subscriptions\Projector\Id;
use Streak\Application\Query\EventListenerQuery;
use Streak\Domain\Event\Listener;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class ListSubscriptions implements EventListenerQuery
{
    const TYPES_NONE = null;
    const COMPLETENESS_COMPLETED_ONLY = true;
    const COMPLETENESS_NOT_COMPLETED_ONLY = false;
    const COMPLETENESS_NONE = null;

    private $types;
    private $completed;

    public function __construct(?array $types = null, ?bool $completed = null)
    {
        $this->types = [] === $types ? null : $types;
        $this->completed = $completed;
    }

    /**
     * @return string[]|null
     */
    public function types() : ?array
    {
        return $this->types;
    }

    public function completed() : ?bool
    {
        return $this->completed;
    }

    public function listenerId() : Listener\Id
    {
        return new Id();
    }
}
