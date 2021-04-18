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

namespace Streak\Infrastructure\Domain\Event\Listener;

use Streak\Domain\Event;
use Streak\Domain\Event\Exception\InvalidEventGiven;
use Streak\Domain\Event\Listener;
use Streak\Domain\Exception;
use Streak\Domain\Exception\InvalidIdGiven;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class CompositeFactory implements Event\Listener\Factory
{
    /**
     * @var Event\Listener\Factory[]
     */
    private array $factories = [];

    public function add(Event\Listener\Factory $factory): void
    {
        $this->factories[] = $factory;
    }

    /**
     * @throws InvalidIdGiven
     */
    public function create(Listener\Id $id): Listener
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
     * @throws InvalidEventGiven
     */
    public function createFor(Event\Envelope $event): Event\Listener
    {
        throw new \BadMethodCallException();
    }
}
