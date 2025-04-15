# Event Bus

The Event Bus is a core component in Streak that enables decoupled communication between different parts of the system through events. It acts as a central hub for publishing events and managing event listeners.

Most developers working with Streak don't need to interact with the Event Bus directly, as it operates transparently in the background. The infrastructure layer automatically handles event publishing and listener management, allowing developers to focus on their domain logic rather than event routing mechanics.

## Interface

The Event Bus is defined by the `EventBus` interface with three primary operations:

```php
<?php

namespace Streak\Domain;

interface EventBus
{
    public function add(Event\Listener $listener): void;
    public function remove(Event\Listener $listener): void;
    public function publish(Event\Envelope ...$events): void;
}
```

## Core Concepts

### Event Publishing

The Event Bus allows components to publish events without knowing who will receive them. Events are published as `Event\Envelope` objects, which wrap the actual event message with metadata like:
- UUID
- Event name
- Producer type and ID
- Entity type and ID (if applicable)
- Version information

### Event Listeners

Components can register as event listeners to receive and process events. Listeners must implement the `Event\Listener` interface and can be:
- Added to the bus using `add()`
- Removed from the bus using `remove()`
- Notified of events through their `on(Event\Envelope $event)` method

### Implementations

#### InMemoryEventBus

The `InMemoryEventBus` is a concrete implementation that:
- Maintains an in-memory collection of listeners using `\SplObjectStorage`
- Delivers events to all registered listeners immediately
- Handles nested event publishing through a publishing flag
- Ensures listeners can be added/removed during event processing
- Stores events in a queue for processing

#### NullEventBus

The `NullEventBus` is a null object implementation that:
- Implements the `EventBus` interface but performs no operations
- Useful for testing or scenarios where event publishing is optional
- Safely discards all events and listener registrations

## Usage Examples

### Publishing Events

```php
<?php

use Streak\Domain\Event;
use Streak\Domain\Event\Envelope;
use Streak\Domain\Id\UUID;

// Create an event envelope
/** @var Event\Envelope $event */
$event = Envelope::new($eventMessage, $producerId);

// Publish the event
/** @var EventBus $eventBus */
$eventBus->publish($event);
```

### Registering a Listener

```php
<?php

namespace App\Application\Listener;

use Streak\Domain\Event;
use Streak\Domain\Event\Listener;

final class MyListener implements Listener
{
    public function on(Event\Envelope $event): bool
    {
        // Handle the event
        return true; // Return true if event was handled
    }
}

/** @var MyListener $listener */
$listener = new MyListener();
/** @var EventBus $eventBus */
$eventBus->add($listener);
```

## Integration with Other Components

The Event Bus is often used in conjunction with:

- **Event Store**: Events can be persisted before or after publishing
- **Projections**: Can subscribe to events to maintain read models
- **Aggregate Roots**: Can publish domain events through the bus
- **Subscriptions**: Can manage long-running event processing

## Best Practices

1. **Event Handling**
   - Listeners should be idempotent
   - Handle events asynchronously when appropriate
   - Return `true` when an event is successfully processed

2. **Error Handling**
   - Listeners should handle their own exceptions
   - Failed event processing should not affect other listeners
   - Consider using event retries for transient failures

3. **Performance**
   - Use appropriate event bus implementation for your scale
   - Consider batching events when possible
   - Monitor listener performance and event processing times
