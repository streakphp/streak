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

namespace Streak\Infrastructure\AggregateRoot\Factory;

use Streak\Domain\AggregateRoot;
use Streak\Domain\Exception;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\AggregateRoot\Factory\CompositeFactoryTest
 */
class CompositeFactory implements AggregateRoot\Factory
{
    private array $factories = [];

    public function __construct(AggregateRoot\Factory ...$factories)
    {
        $this->factories = $factories;
    }

    public function add(AggregateRoot\Factory $factory)
    {
        $this->factories[] = $factory;
    }

    public function create(AggregateRoot\Id $id) : AggregateRoot
    {
        $last = null;
        foreach ($this->factories as $factory) {
            try {
                return $factory->create($id);
            } catch (Exception\InvalidAggregateIdGiven $current) {
                $last = new Exception\InvalidAggregateIdGiven($id, $current);
            }
        }

        throw new Exception\InvalidAggregateIdGiven($id, $last);
    }
}
