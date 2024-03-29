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

namespace Streak\Domain\Event\Sourced;

use Streak\Domain;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Entity extends Domain\Entity, Event\Consumer
{
    public function registerAggregateRoot(Event\Sourced\AggregateRoot $aggregate): void;

    public function registerAggregate(Event\Sourced\Aggregate $aggregate): void;

    public function aggregateRoot(): Event\Sourced\AggregateRoot;

    public function aggregate(): ?Event\Sourced\Aggregate;
}
