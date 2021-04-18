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

namespace Streak\Infrastructure\Testing\AggregateRoot;

use PHPUnit\Framework\Assert;
use Streak\Domain;
use Streak\Infrastructure\Domain\AggregateRoot\Snapshotter;
use Streak\Infrastructure\Domain\EventStore\InMemoryEventStore;
use Streak\Infrastructure\Domain\UnitOfWork;
use Streak\Infrastructure\Testing\AggregateRoot\Scenario\Given;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @codeCoverageIgnore
 */
class Scenario implements Scenario\Given, Scenario\When, Scenario\Then
{
    private Domain\CommandHandler $handler;
    private InMemoryEventStore $store;
    private Domain\AggregateRoot\Factory $factory;
    private Snapshotter $snapshotter;
    private UnitOfWork $uow;
    private ?Domain\AggregateRoot\Id $id = null;
    /**
     * @var Domain\Event\Envelope[]
     */
    private array $events = [];

    public function __construct(Domain\CommandHandler $handler, InMemoryEventStore $store, Domain\AggregateRoot\Factory $factory, Snapshotter $snapshotter, UnitOfWork $uow)
    {
        $this->handler = $handler;
        $this->store = $store;
        $this->factory = $factory;
        $this->snapshotter = $snapshotter;
        $this->uow = $uow;
    }

    public function for(Domain\AggregateRoot\Id $id): Given
    {
        $this->id = $id;

        return $this;
    }

    public function given(Domain\Event ...$events): Scenario\When
    {
        $version = 0;
        foreach ($events as $key => $event) {
            $events[$key] = Domain\Event\Envelope::new($event, $this->id, ++$version);
        }
        $this->events = $events;
        $this->store->add(...$this->events);

        return $this;
    }

    public function when(Domain\Command $command): Scenario\Then
    {
        $this->handler->handleCommand($command);

        return $this;
    }

    public function then(Domain\Event ...$expected): void
    {
        $this->events = array_merge($this->events, $expected);

        Assert::assertNotEmpty($this->events, 'No events provided for scenario.');

        $this->store->clear();

        /** @var Domain\AggregateRoot[] $uncommitted */
        $uncommitted = $this->uow->uncommitted();

        Assert::assertCount(1, $uncommitted, 'Only one aggregate root should be used during command execution.');

        $uncommitted = array_pop($uncommitted);

        Assert::assertInstanceOf(Domain\AggregateRoot::class, $uncommitted, 'Detected event producer is not an aggregate root.');

        $new = $this->factory->create($this->id);

        Assert::assertTrue($new->equals($uncommitted), 'Wrong aggregate root detected.');

        iterator_to_array($this->uow->commit());

        $actual = iterator_to_array($this->store->stream());

        Domain\Event\Metadata::clear(...$actual);

        // unpack events from envelopes
        $actual = array_map(fn (Domain\Event\Envelope $envelope) => $envelope->message(), $actual);

        Assert::assertEquals($expected, $actual, 'Expected events don\'t match produced events.');

        $this->snapshotter->takeSnapshot($uncommitted);
        $snapshot = $this->snapshotter->restoreToSnapshot($new) ?: $uncommitted;

        // if no snapshot was produced we actually compare two same objects, but its okay, we dont care at this point
        Assert::assertEquals($snapshot, $uncommitted, 'Snapshot does not reconstitute full aggregate state.');
    }
}
