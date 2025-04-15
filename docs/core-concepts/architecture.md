# Core Concepts: Architecture

This document explains the core architectural patterns and building blocks of the Streak system.

## Overview

Streak is built on three fundamental architectural patterns:

1. **Domain-Driven Design (DDD)**
   - Rich domain model with clear boundaries
   - Aggregates enforce consistency
   - Value objects and entities model business concepts

2. **Command Query Responsibility Segregation (CQRS)**
   - Commands modify state through aggregates
   - Queries read from optimized read models
   - Clear separation between write and read paths

3. **Event Sourcing**
   - State changes are recorded as events
   - Current state is derived from event history
   - Enables temporal queries and audit trails

## System Flow

The system follows a clear flow for handling changes:

1. **Command Dispatch**
   - A command is dispatched to the Command Bus
   - Commands represent an intent to change the system's state
   - See [Commands](commands.md) for details

2. **Command Handling**
   - Command Bus routes the command to its handler
   - Handler validates the command and business rules
   - Handler creates or loads the appropriate aggregate
   - See [Commands](commands.md) for implementation details

3. **Aggregate Processing**
   - Aggregate processes the command
   - Aggregate records events for state changes
   - Aggregate maintains consistency boundaries
   - See [Building an Aggregate](../tutorials/building-an-aggregate.md) for details

4. **Event Storage**
   - Events are stored in the Event Store
   - Events are stored atomically with the command
   - See [Event Store](event-store.md) for details

5. **Event Publishing**
   - Events are published to the Event Bus
   - Publishing happens after successful storage
   - See [Event Bus](event-bus.md) for details

6. **Event Processing**
   - Event Bus routes events to listeners
   - Listeners react to events (e.g., update read models)
   - See [Listeners](listeners.md) for details

## Domain Model Building Blocks

Streak provides a rich set of building blocks for implementing Domain-Driven Design:

### Value Objects

Immutable objects that are defined by their attributes:

```php
<?php

namespace Domain\Project;

use Streak\Domain;

interface ValueObject extends Comparable
{
    public function equals(ValueObject $other): bool;
}

interface Id extends ValueObject
{
    public function toString(): string;
    public static function fromString(string $id): self;
}
```

### Entities

Objects that have a distinct identity and lifecycle:

```php
<?php

namespace Domain\Project;

use Streak\Domain;

interface Entity extends Identifiable, Comparable
{
    public function id(): Entity\Id;
    public function equals(Entity $other): bool;
}

interface Entity\Id extends Id {}
```

### Aggregates

Clusters of related objects (other aggregates, entities, and value objects) that form consistency boundaries. The top most aggregate in the hierarchy is the Aggregate Root.

```php
<?php

namespace Domain\Project;

use Streak\Domain;

interface Aggregate extends Entity
{
    public function id(): Aggregate\Id;
}

interface AggregateRoot extends Aggregate
{
    public function id(): AggregateRoot\Id;
}

interface Aggregate\Id extends Entity\Id {}
interface AggregateRoot\Id extends Aggregate\Id {}
```

Streak requires aggregate roots to have public constructors, allowing for direct instantiation while still supporting static factory methods for convenience.

### Repositories

Repositories are designed to work exclusively with aggregate roots. They follow a collection-like pattern where you add an aggregate root once, and then all changes are tracked transparently. This design ensures that:

1. Only aggregate roots can be persisted and retrieved
2. Entities and value objects within aggregates remain encapsulated as its impossible to retrieve them outside of the aggregate root
3. Changes to aggregates are automatically tracked without explicit save calls

```php
<?php

namespace Streak\Domain\AggregateRoot;

use Streak\Domain;

interface Repository
{
    public function add(Domain\AggregateRoot $aggregate): void;

    public function find(Domain\AggregateRoot\Id $id): ?Domain\AggregateRoot;
}
```

This simple interface reflects Streak's approach to persistence.

#### Adding a New Aggregate

When creating a new aggregate root, you add it to the repository once. This registers the aggregate with the repository, which will then track all changes to it thanks to built-in Unit of Work.

```php
<?php

use Domain\Project\Project;
use Domain\Project\ProjectId;
use Streak\Domain\AggregateRoot\Repository;

/** @var Repository $repository */
$project = new Project(new ProjectId('uuid'), 'Name');

$repository->add($project);
```

To work with an existing aggregate, you find it by its ID and then make changes directly:

```php
<?php

use Domain\Project\Project;
use Domain\Project\ProjectId;
use Streak\Domain\AggregateRoot\Repository;

/** @var Repository $repository */
/** @var Project $project */
$project = $repository->find(new ProjectId('uuid'));

$project->doSomething();

// $repository->add($project);
```

The repository automatically tracks all changes made to the aggregate after it's been loaded. This means you don't need to explicitly save the aggregate after making changes - the repository handles persistence transparently.

Since repositories only work with aggregate roots, you can't directly access or modify entities within an aggregate as its impossible from type safety point of view.

Streak provides a concrete implementation of this interface for aggregate roots, so you don't need to create your own repository implementations.

## Related Concepts

* [Commands](commands.md) - Command patterns and handling
* [Events](events.md) - Event patterns and handling
* [Event Store](event-store.md) - Event storage and retrieval
* [Event Bus](event-bus.md) - Event distribution
* [Listeners](listeners.md) - Event processing
* [Queries](queries.md) - Read model patterns
