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

namespace Streak\Domain\Event\Subscription\Criteria;

use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Subscription\Criteria;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class TypeEqualsToCriteria implements Criteria
{
    private $type;

    public function __construct(string $type)
    {
        $this->validate($type);

        $this->type = $type;
    }

    public function type() : string
    {
        return $this->type;
    }

    public function accept(Visitor $visitor)
    {
        $visitor->visitTypeEqualsTo($this);
    }

    private function validate(string $type) : void
    {
        try {
            $reflection = new \ReflectionClass($type);
        } catch (\ReflectionException $exception) {
            throw new \InvalidArgumentException(
                sprintf('Given argument "%s" is not a type of "%s"', $type, Listener\Id::class)
            );
        }

        if (false === $reflection->implementsInterface(Listener\Id::class)) {
            throw new \InvalidArgumentException(
                sprintf('Given argument "%s" is not a type of "%s"', $type, Listener\Id::class)
            );
        }
    }
}
