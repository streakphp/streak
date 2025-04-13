# Core Concepts: Events

Domain Events are central to Event Sourcing and represent facts about things that have occurred in the system. In Streak, they are the sole source of truth for the state of your Aggregate Roots.

## Domain Events

Events capture state changes. Instead of mutating state directly and saving the current state, event-sourced aggregates apply events which are then persisted. Examples include `UserRegistered`, `OrderPlaced`, `TaskCompleted`.

Key characteristics of Domain Events:

*   **Immutable:** Once an event has occurred and been recorded, it cannot be changed.
*   **Past Tense:** Event names typically reflect something that has already happened (e.g., `ProjectCreated`, not `CreateProject`).
*   **Data Carriers:** Events carry the data necessary to understand what changed (e.g., the user's ID and email in `UserRegistered`).

In Streak, Domain Events are typically simple PHP objects (often Plain Old PHP Objects or POPOs) that implement the marker interface `Streak\Domain\Event`.

```php
<?php

namespace My\Domain\Events;

use Streak\Domain\Event;

final class ProjectCreated implements Event
{
    public function __construct(
        public readonly string $projectId,
        public readonly string $projectName,
        public readonly string $creatorId,
        public readonly \DateTimeImmutable $createdAt
    ) {}
}
```

## Event Envelopes

When events are persisted to the Event Store or published on an Event Bus, they are often wrapped in an `Envelope`. The envelope adds metadata to the event without cluttering the event object itself.

Streak uses the `Streak\Domain\Envelope` interface:

```php
<?php

namespace Streak\Domain;

use Streak\Domain\Id\UUID;

interface Envelope extends ValueObject
{
    // A unique identifier for this specific instance of the event.
    public function uuid(): UUID;

    // The name or type of the event message (e.g., FQCN of the event class).
    public function name(): string;

    // The actual Domain Event object.
    public function message(); // Typically returns Domain\Event

    // Access to arbitrary metadata associated with the event.
    public function get($name);
    public function metadata(): array;
}
```

Common metadata might include:

*   Timestamp of when the event was recorded.
*   Causation ID (ID of the command or event that caused this event).
*   Correlation ID (ID to track a flow across multiple aggregates or contexts).
*   Aggregate Root ID and version.
*   User ID who initiated the action.

Envelopes are typically created internally by the framework (e.g., by the `EventSourcing` trait or the `EventStore` implementation) when an aggregate applies an event or when events are persisted.

## Usage

*   **Creation:** Events are instantiated within Aggregate Root command handlers (or other logic) after a command is successfully validated and business rules are met. They capture the results of the command.
*   **Applying:** The aggregate calls `$this->apply(new MyEvent(...));`. The `EventSourcing` trait usually handles wrapping this in an envelope and storing it internally until persisted.
*   **Persisting:** The Event Store receives and persists `Envelope` objects.
*   **Rehydrating:** When loading an aggregate, the Event Store retrieves the historical `Envelope`s, extracts the `message()` (the event), and the `EventSourcing` trait calls the appropriate `apply<EventName>` method on the aggregate for each event.
