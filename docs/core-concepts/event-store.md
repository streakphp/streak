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
     * Adds one or more event envelopes to the store.
     *
     * Implementations should handle concurrency control (e.g., using expected version).
     *
     * @param Event\Envelope ...$events The events to add.
     * @return Event\Envelope[] The added events (potentially enriched with store-specific info).
     * @throws Exception\ConcurrentWriteDetected If a concurrency conflict occurs.
     * @throws Exception\InvalidAggregateGiven Potentially for store-specific validation.
     */
    public function add(Event\Envelope ...$events): array;

    /**
     * Retrieves a stream of event envelopes, optionally filtered.
     *
     * @param EventStore\Filter|null $filter Optional filter criteria.
     * @return Event\Stream A stream (potentially lazy-loaded) of matching event envelopes.
     */
    public function stream(?EventStore\Filter $filter = null): Event\Stream;

    /**
     * Retrieves a single event envelope by its unique UUID.
     *
     * @param UUID $uuid The UUID of the envelope.
     * @return Event\Envelope|null The found envelope or null.
     */
    public function event(UUID $uuid): ?Event\Envelope;
}
```

Key responsibilities:

*   **Append Events:** Atomically appending new events (`add`) to the stream for a specific aggregate, often handling concurrency checks.
*   **Read Streams:** Retrieving the sequence of events (`stream`) for a given aggregate ID or based on other filter criteria.
*   **Read Single Event:** Retrieving a specific event by its unique ID (`event`).

## Event Streams

The `stream()` method returns an `Streak\Domain\Event\Stream`. This interface typically represents an iterable sequence of `Envelope` objects. Implementations might be lazy-loaded to avoid fetching thousands of events into memory at once.

## Filtering Streams

The `stream()` method accepts an optional `Streak\Domain\EventStore\Filter`. Filters allow querying the event store for specific subsets of events. Common filter criteria include:

*   Filtering by Aggregate Root type and ID.
*   Filtering events after a certain version or point in time.
*   Filtering by event type(s).

Streak provides basic filter implementations, and you can create custom ones.

## Implementations

Streak provides several `EventStore` implementations out of the box:

1.  **`Streak\Infrastructure\Domain\EventStore\InMemoryEventStore`**
    *   Stores events in a PHP array in memory.
    *   **Use Cases:** Primarily for testing (unit, integration) or very simple applications where persistence across requests is not needed.
    *   **Pros:** Fast, no external dependencies.
    *   **Cons:** Data is lost when the process ends.

2.  **`Streak\Infrastructure\Domain\EventStore\DbalPostgresEventStore`**
    *   Stores events in a PostgreSQL database using Doctrine DBAL.
    *   Requires specific database schema (migrations might be needed).
    *   Handles event serialization (often requires a configured `Serializer`).
    *   Implements optimistic concurrency control using event versions.
    *   **Use Cases:** Production environments requiring persistent storage.
    *   **Pros:** Durable, leverages mature database technology, supports concurrency checks.
    *   **Cons:** Requires PostgreSQL, setup involves DBAL configuration and schema management.

3.  **`Streak\Infrastructure\Domain\EventStore\PublishingEventStore`**
    *   This is a **Decorator**. It wraps another `EventStore` implementation.
    *   Ensures events are delivered to listeners after being stored.
    *   Publishes events to an in-memory event bus used by Streak's infrastructure (e.g., for starting [subscriptions](./listeners.md#running-listeners)).
    *   Guarantees that events are only published after successful persistence.
    *   **Use Cases:** 
        * Reliable event delivery to listeners
        * Maintaining consistency between event store and listeners
        * Ensuring events are processed in the order they were stored
    *   **Configuration:** Takes the underlying `EventStore` and event publishing mechanism as constructor arguments.

## Choosing an Implementation

*   Use `InMemoryEventStore` for your tests.
*   Use `DbalPostgresEventStore` (or potentially other DBAL-based stores if created) for development and production if using PostgreSQL.
*   **Always** wrap your primary persistent event store with `PublishingEventStore` to ensure reliable event delivery to listeners.

Configuration, especially for the DBAL store and serialization, is typically handled via dependency injection, often simplified by the `StreakBundle` if using Symfony. 
