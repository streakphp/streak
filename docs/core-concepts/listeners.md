# Core Concepts: Event Listeners

Event Listeners react to [Domain Events](./events.md) in your application, decoupling side effects from core command processing. Common use cases include [Process Managers](../tutorials/building-a-process-manager.md) and [Sagas](../tutorials/building-a-saga.md).

## What is an Event Listener?

Event Listeners process [Domain Events](./events.md) through Subscriptions. Subscriptions ensure reliable event processing by reading from the [Event Store](./event-store.md).

The `Listener` interface defines the core contract:

```php
<?php

namespace Streak\Domain\Event;

use Streak\Domain\Event;
use Streak\Domain\Identifiable;

interface Listener extends Identifiable
{
    public function id(): Listener\Id;

    /**
     * @return bool whether event was processed/is supported
     */
    public function on(Event\Envelope $event): bool;
}
```

Key aspects:
* Unique identification for each listener instance
* Event processing capabilities through the `on()` method
* Optional features for reset, completion, state management, and querying

For implementation details, see our [tutorials](../tutorials/).

## Listener Extensions

Streak provides several extension interfaces for listeners:

```php
// For listeners that can complete their work
interface Completable
{
    public function completed(): bool;
}

// For listeners that can be reset to initial state
interface Resettable
{
    public function reset(): void;
}

// For listeners that maintain state
interface Stateful
{
    public function toState(State $state): State;
    public function fromState(State $state);
}
```

## Listener Factories

Factories create and manage listener instances:

```php
<?php

namespace Streak\Domain\Event\Listener;

use Streak\Domain\Event;
use Streak\Domain\Event\Exception\InvalidEventGiven;
use Streak\Domain\Event\Listener;
use Streak\Domain\Exception\InvalidIdGiven;

interface Factory
{
    /**
     * @throws InvalidIdGiven
     */
    public function create(Listener\Id $id): Listener;

    /**
     * @throws InvalidEventGiven
     */
    public function createFor(Event\Envelope $event): Event\Listener;
}
```

The factory interface provides two main operations:
1. Create new listeners from events
2. Load existing listeners by ID
3. Manage dependencies

For practical examples, see our [Process Manager](../tutorials/building-a-process-manager.md) and [Saga](../tutorials/building-a-saga.md) tutorials.

## Event Flow

Events flow from Event Bus → Event Store → Subscription → Listener

## Idempotency

Listeners must handle duplicate events safely through:

* Idempotent operations
* State checks
* Event tracking

For real-world examples, check our [Process Manager tutorial](../tutorials/building-a-process-manager.md).

## Testing Event Listeners

For detailed examples of testing event listeners using the Given-When-Then pattern, see the [Testing Documentation](testing.md).

## Subscriptions

Subscriptions are the operational layer that makes listeners reliable and persistent. They wrap event listeners to provide crucial capabilities like position tracking, state management, and error handling.

The `Subscription` interface defines the core contract:

```php
<?php

namespace Streak\Domain\Event;

use Streak\Domain\Event;
use Streak\Domain\Event\Subscription\Exception;
use Streak\Domain\EventStore;

interface Subscription
{
    public function listener(): Event\Listener;

    public function id(): Event\Listener\Id;

    /**
     * @throws Exception\SubscriptionAlreadyCompleted
     * @throws Exception\SubscriptionNotStartedYet
     *
     * @return Event\Envelope[]|iterable
     */
    public function subscribeTo(EventStore $store, ?int $limit = null): iterable;

    public function startFor(Event\Envelope $event): void;

    /**
     * @throws Exception\SubscriptionNotStartedYet
     * @throws Exception\SubscriptionRestartNotPossible
     */
    public function restart(): void;

    public function paused(): bool;

    public function pause(): void;

    public function unpause(): void;

    public function starting(): bool;

    public function started(): bool;

    public function completed(): bool;

    public function version(): int;
}
```

### Subscription Lifecycle

Subscriptions have several states they can be in:

1. **Not Started**: Initial state before processing any events
2. **Starting**: Transitional state during initialization (check with `starting()`)
3. **Running**: Actively processing events (check with `started()` and not `paused()`)
4. **Paused**: Temporarily stopped but maintains position (check with `paused()`)
5. **Completed**: Finished processing (check with `completed()`)

### State Persistence

Streak offers two approaches for persisting subscription state:

#### Event Sourced Subscriptions

* Subscription changes are stored as events
* These events live in the same event store as domain events
* Maintains complete history of subscription lifecycle
* Enables replay of subscription state if needed
* Natural fit when treating subscriptions as first-class domain concepts
* Subscription events are stored in the same transaction as aggregate events
* Guarantees consistency between subscription state and domain events

#### Direct State Storage

* Current subscription state stored directly in database
* Uses dedicated tables for tracking position and status
* Optimized for quick access to current state
* Simpler implementation for basic scenarios
* More efficient when history isn't needed
* State updates may occur in separate transactions if using different storage backends
* Requires careful consideration of transaction boundaries when storage differs

The choice between these approaches depends on your needs:
* Use event sourcing when subscription history is valuable
* Use direct storage when only current state matters
* Consider transaction boundaries in your consistency requirements
* Both approaches are reliable, but with different guarantees

### Core Operations

#### Starting

* Subscriptions must be started with an initial event
* This sets the starting position in the event stream
* Cannot start an already started subscription

#### Event Processing

* Reads events from the Event Store
* Delivers events to the wrapped listener
* Tracks the last processed position
* Can limit the number of events processed
* Handles errors and retries

#### Pausing and Resuming

* Can pause active subscriptions
* Maintains the last processed position
* Can resume from the last position
* Useful for maintenance or error recovery

#### Restarting

* Available for resettable listeners
* Clears the listener's state
* Starts processing from the beginning
* Useful for rebuilding projections

### Command Line Management

The following commands are available for managing subscriptions:

* `streak:subscriptions:run` - Run multiple subscriptions
* `streak:subscription:run` - Run a specific subscription
* `streak:subscription:restart` - Reset and restart a subscription
* `streak:subscription:pause` - Pause a subscription
* `streak:subscription:unpause` - Resume a paused subscription

For detailed command usage, see the [Symfony Bundle documentation](../symfony-bundle/console-commands.md).

