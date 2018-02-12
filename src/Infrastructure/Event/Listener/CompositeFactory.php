<?php

/**
 * This file is part of the cbs package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Streak\Infrastructure\Event\Listener;

use Streak\Domain;
use Streak\Domain\Exception;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class CompositeFactory implements Event\Listener\Factory
{
    /**
     * @var Event\Listener\Factory[]
     */
    private $factories = [];

    public function add(Event\Listener\Factory $factory)
    {
        $this->factories[] = $factory;
    }

    /**
     * @throws Exception\InvalidIdGiven
     */
    public function create(Domain\Id $id) : Listener
    {
        foreach ($this->factories as $factory) {
            try {
                return $factory->create($id);
            } catch (Exception\InvalidIdGiven $e) {
                continue;
            }
        }

        throw new Exception\InvalidIdGiven($id);
    }

    /**
     * @throws Exception\InvalidIdGiven
     */
    public function createFor(Event $event) : Listener
    {
        throw new \BadMethodCallException();
    }

}
