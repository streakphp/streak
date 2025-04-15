# Domain Events

Domain Events represent facts about things that have occurred in the system. They are the source of truth for [aggregate state](architecture.md#aggregates) in event sourcing.

## What is a Domain Event?

Unlike [Commands](commands.md) (which request changes), Events record what has happened. Streak defines the core `Event` interface:

```php
<?php

namespace Streak\Domain;

interface Event
{
}
```

Example implementation:

```php
<?php

namespace Domain\Project\Event;

use Streak\Domain\Event;

final class ProjectCreated implements Event
{
    public function __construct(
        private string $projectId,
        private string $name,
        private string $creatorId,
        private \DateTimeImmutable $createdAt
    ) {
    }
    
    public function getProjectId(): string
    {
        return $this->projectId;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getCreatorId(): string
    {
        return $this->creatorId;
    }
    
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
```

Key characteristics:

*   **Immutable:** Events cannot be modified after creation
*   **Past Tense:** Names reflect completed actions (`ProjectCreated`, `TaskCompleted`)
*   **Factual:** Capture exactly what changed, without interpretation

## Event Envelopes

Events are wrapped in envelopes that add metadata without modifying the event itself. Streak provides the `Envelope` class which implements the `Domain\Envelope` interface.

```php
<?php

namespace Streak\Domain\Event;

use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Id\UUID;

final class Envelope implements Domain\Envelope
{
    public static function new(Event $event, Domain\Id $producerId, ?int $version = null): self
    {
        return new self(UUID::random(), $event::class, $event, $producerId, $version);
    }
    
    // Other methods...
}
```

Example usage:

```php
<?php

use Domain\Project\Event\ProjectCreated;
use Domain\Project\ProjectId;
use Streak\Domain\Event\Envelope;

/** @var ProjectCreated $event */
$event = new ProjectCreated('project-123', 'My Project', 'user-456', new \DateTimeImmutable());

/** @var ProjectId $producerId */
$producerId = new ProjectId('project-123');

/** @var Envelope $envelope */
$envelope = Envelope::new($event, $producerId);
```

Common metadata includes:
*   Event UUID
*   Event name/type  
*   Producer ID and type
*   Entity ID and type (if applicable)
*   Version information

Envelopes are typically created by the system automatically during event handling and rarely need to be created manually. You'll interact with the envelope when implementing event listeners.

## Entity Events

For events that are specific to an entity within an aggregate, Streak provides the `EntityEvent` interface:

```php
<?php

namespace Streak\Domain\Event;

use Streak\Domain\Entity;
use Streak\Domain\Event;

interface EntityEvent extends Event
{
    public function entityId(): Entity\Id;
}
```

Example implementation for an entity event:

```php
<?php

namespace Domain\Project\Event;

use Domain\Project\Task\TaskId;
use Streak\Domain\Entity;
use Streak\Domain\Event\EntityEvent;

final class TaskCompleted implements EntityEvent
{
    public function __construct(
        public readonly string $projectId,
        public readonly string $taskId,
        public readonly \DateTimeImmutable $completedAt
    ) {
    }
    
    public function entityId(): Entity\Id
    {
        return new TaskId($this->taskId);
    }
}
```

When creating an envelope for an entity event, the system automatically captures both the aggregate root ID and the entity ID:

```php
<?php

use Domain\Project\Event\TaskCompleted;
use Domain\Project\ProjectId;
use Streak\Domain\Event\Envelope;

$taskCompleted = new TaskCompleted(
    'project-123',
    'task-456',
    new \DateTimeImmutable()
);

$projectId = new ProjectId('project-123');
$envelope = Envelope::new($taskCompleted, $projectId);

// The envelope now contains:
// - Producer ID/Type (ProjectId/project-123)
// - Entity ID/Type (TaskId/task-456)
```

## Event Consistency

Events maintain several key guarantees:

### Stream Boundaries
* Each aggregate has its own event stream
* Events in a stream are strictly ordered
* Multiple streams evolve independently

### Event Ordering
* Version numbers define event sequence
* Listeners see events in production order
* Cross-stream order maintained by subscriptions

### Transactional Guarantees
* Events from one command stored atomically
* Event publishing follows successful storage
* Listeners always see consistent state

For implementation details, see:
* [Event Store](event-store.md) - How events are persisted
* [Event Bus](event-bus.md) - How events are distributed
* [Building an Aggregate](../tutorials/building-an-aggregate.md) - How events are applied to aggregates
