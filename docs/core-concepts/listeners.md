# Core Concepts: Event Listeners

Event Listeners react to [Domain Events](./domain-events.md) in your application, decoupling side effects from core command processing. Common use cases include [Process Managers](../tutorials/building-a-process-manager.md) and [Sagas](../tutorials/building-a-saga.md).

## What is an Event Listener?

Event Listeners process [Domain Events](./domain-events.md) through [Subscriptions](./subscriptions.md). Subscriptions ensure reliable event processing by reading from the [Event Store](./event-store.md).

The core interface is:

```php
<?php

namespace Streak\Domain\Event;

interface Listener
{
    public function id(): Listener\Id;
    public function on(Event\Envelope $envelope): bool;
}
```

Key aspects:
*   **`id()`:** Unique identifier for the listener instance
*   **`on(Event\Envelope $envelope)`:** Processes an event

### Optional Interfaces

*   **`Listener\Resettable`:** Enables reprocessing all events from the beginning
*   **`Listener\Completable`:** Indicates completion status (see [Process Manager tutorial](../tutorials/building-a-process-manager.md))
*   **`Listener\Stateful`:** Enables state persistence
*   **`Streak\Domain\QueryHandler`:** Enables querying listener's data (see [Query Handling tutorial](../tutorials/query-handling.md))

## Listener Factories

Factories create and manage listener instances:

1. Create new listeners from events
2. Load existing listeners by ID
3. Manage dependencies

For practical examples, see our [Process Manager](../tutorials/building-a-process-manager.md) and [Saga](../tutorials/building-a-saga.md) tutorials.

## Event Flow

Events flow from Event Bus → Event Store → Subscription → Listener

## Subscriptions

Subscriptions manage event processing:

*   Poll the Event Store
*   Process events reliably
*   Track position and state
*   Handle retries and pausing

## Idempotency

Listeners must handle duplicate events safely through:

* Idempotent operations
* State checks
* Event tracking

For real-world examples, check our [Process Manager tutorial](../tutorials/building-a-process-manager.md).

## Example

```php
<?php

namespace App\Application\Listener;

use Streak\Domain\Event;
use Streak\Domain\Event\Listener;

final class ExampleListener implements Listener
{
    use Event\Listener\Identifying;
    use Event\Listener\Listening;

    public function __construct(private ExampleListener\Id $id)
    {
        $this->identifyBy($id);
    }

    public function onExampleEvent(ExampleEvent $event): void
    {
        // Handle the specific event
    }
}
```

For more detailed examples and best practices, see our [tutorials](../tutorials/).

## Running Listeners

Listeners run through Subscriptions - a mechanism that ensures reliable event processing from the Event Store.

### Key Aspects

* **Position Tracking:** Tracks which events have been processed
* **State Persistence:** Maintains listener state across restarts
* **Reliable Processing:** Guarantees at-least-once event delivery
* **Independent Operation:** Each listener instance runs separately

### Starting Position

When a listener starts for the first time:
* By default, it starts from the event that triggered/started it
* Implement `Event\Picker` interface to define a custom starting point
* Position is saved after each event, enabling resume after restart

### Managing Listeners

Use these commands to manage your listeners:

```bash
# Run all listeners
streak:subscriptions:run

# Run all listeners of specific type
streak:subscriptions:run "App\Listener\ExampleListener\Id"

# Run specific listener instance
streak:subscription:run "App\Listener\ExampleListener\Id" "example-1"

# Restart specific listener instance
streak:subscription:restart "App\Listener\ExampleListener\Id" "example-1"

# Pause specific listener instance
streak:subscription:pause "App\Listener\ExampleListener\Id" "example-1"

# Unpause specific listener instance
streak:subscription:unpause "App\Listener\ExampleListener\Id" "example-1"
```

### Benefits

* Reliable event processing even after restarts
* Independent scaling of different listeners
* Ability to replay historical events
* Built-in error handling and retries
