# Core Concepts: Event Store

The Event Store is the component responsible for the persistence of event streams. It's the backbone of an Event Sourcing system, acting as the database for your events.

## The `EventStore` Interface

Streak defines the core contract for event stores through the `Streak\Domain\EventStore` interface:

```php
<?php

namespace Streak\Domain;

use Streak\Domain\Id\UUID;

interface EventStore
{
    /**
     * @throws Exception\ConcurrentWriteDetected
     * @throws Exception\InvalidAggregateGiven
     */
    public function add(Event\Envelope ...$events): array;

    public function stream(?EventStore\Filter $filter = null): Event\Stream;

    public function event(UUID $uuid): ?Event\Envelope;
}
```

Key responsibilities:

*   **Append Events:** Atomically appending new events (`add`) to the stream, handling concurrency checks and validation.
*   **Read Streams:** Retrieving event streams (`stream`) with optional filtering.
*   **Read Single Event:** Retrieving a specific event by its UUID (`event`).

## Event Streams

The `stream()` method returns a `Streak\Domain\Event\Stream`. This interface represents an iterable sequence of `Envelope` objects. Implementations might be lazy-loaded to avoid fetching thousands of events into memory at once.

## Event Envelopes

Events are wrapped in envelopes that add metadata without modifying the event itself:

```php
<?php

namespace Streak\Domain;

use Streak\Domain\Id\UUID;

interface Envelope extends ValueObject
{
    public function uuid(): UUID;

    public function name(): string;

    public function message();

    /**
     * @param string $name
     *
     * @return float|int|string|null
     */
    public function get($name);

    public function metadata(): array;
}
```

Common metadata includes:
*   Event UUID
*   Event name/type
*   Event message (the actual event)
*   Additional metadata fields

## Filtering Streams

The `stream()` method accepts an optional `Streak\Domain\EventStore\Filter`. Filters allow querying the event store for specific subsets of events. Common filter criteria include:

*   Filtering by Aggregate Root type and ID
*   Filtering events after a certain version or point in time
*   Filtering by event type(s)

Streak provides basic filter implementations, and you can create custom ones.

## Implementations

Streak provides several `EventStore` implementations out of the box:

1.  **`Streak\Infrastructure\Domain\EventStore\InMemoryEventStore`**
    *   Stores events in memory using PHP arrays
    *   Maintains both a flat list of all events and stream-based organization
    *   Implements optimistic concurrency control through version checking
    *   **Use Cases:** Primarily for testing (unit, integration) or very simple applications
    *   **Pros:** Fast, no external dependencies
    *   **Cons:** Data is lost when the process ends

2.  **`Streak\Infrastructure\Domain\EventStore\DbalPostgresEventStore`**
    *   Stores events in PostgreSQL using Doctrine DBAL
    *   Uses JSONB for event data and metadata storage
    *   Implements both EventStore and Event\Stream interfaces
    *   Provides schema management through Schemable interface
    *   **Use Cases:** Production environments requiring persistent storage
    *   **Pros:** Durable, leverages mature database technology
    *   **Cons:** Requires PostgreSQL, setup involves DBAL configuration

3.  **`Streak\Infrastructure\Domain\EventStore\PublishingEventStore`**
    *   Decorator that combines event storage with event publishing
    *   Ensures events are only published after successful storage
    *   Events are published through an [Event Bus](event-bus.md)
    *   Handles recursive publishing through a working flag
    *   Implements Schemable interface when the decorated store does
    *   **Use Cases:** 
        * Reliable event delivery to listeners
        * Maintaining consistency between event store and listeners
        * Ensuring events are processed in the order they were stored

## Choosing an Implementation

*   Use `InMemoryEventStore` for your tests
*   Use `DbalPostgresEventStore` for development and production if using PostgreSQL
*   **Always** wrap your primary persistent event store with `PublishingEventStore` to ensure reliable event delivery

For implementation details, see:
* [Event Bus](event-bus.md) - How events are distributed to listeners
* [Subscriptions](listeners.md#subscriptions) - How event listeners track their position
* [Building an Aggregate](../tutorials/building-an-aggregate.md) - How aggregates use the event store
* [Testing](testing.md) - How to test event-sourced aggregates
