# Core Concepts: Aggregates & Event Sourcing

Aggregates are a fundamental concept from Domain-Driven Design (DDD) that Streak leverages heavily, especially in the context of Event Sourcing.

## What is an Aggregate?

An Aggregate is a cluster of domain objects (Entities and Value Objects) that can be treated as a single unit. It has a root entity, known as the **Aggregate Root**, which is the only member of the aggregate that external objects are allowed to hold references to.

The primary purpose of an Aggregate is to enforce **consistency boundaries**. Business rules and invariants that span multiple objects within the aggregate are enforced by the Aggregate Root. All changes to the state within the aggregate must go through the Aggregate Root.

In Streak, Aggregates are the primary building blocks for your domain logic when using Event Sourcing.

## Core Interfaces

Streak defines several interfaces to represent these concepts:

*   `Streak\Domain\ValueObject`: A marker interface for Value Objects, indicating they should be compared by value (requires an `equals` method, typically inherited).
*   `Streak\Domain\Id`: Represents an identifier, extending `ValueObject`. Requires `toString(): string` and static `fromString(string $id): self` methods.
*   `Streak\Domain\Entity`: Represents an entity within the domain, which has a distinct identity and is compared by that identity. Requires an `id(): Entity\Id` method and an `equals` method (typically inherited).
*   `Streak\Domain\Aggregate`: Represents an aggregate boundary, extending `Entity`. Requires `id(): Aggregate\Id`.
*   `Streak\Domain\AggregateRoot`: Represents the root of an aggregate, extending `Aggregate`. Requires `id(): AggregateRoot\Id`.

The hierarchy (`AggregateRoot` -> `Aggregate` -> `Entity`) requires specific types of IDs at different levels, although often a single ID type (like a UUID) implementing `AggregateRoot\Id` is used.

## Identifiers (IDs)

IDs in Streak are Value Objects. They uniquely identify Entities and Aggregate Roots. The base `Streak\Domain\Id` interface ensures they can be easily converted to and from strings, which is crucial for persistence and referencing.

```php
<?php

use Streak\Domain;

interface Id extends Domain\ValueObject
{
    public function toString(): string;
    public static function fromString(string $id): Domain\Id; // Often returns static or self
}
```

Common implementations often use UUIDs.

## Loading and Saving Aggregates (Repositories)

While Aggregate Roots encapsulate domain logic, **Repositories** handle the persistence concerns: loading aggregates from the `EventStore` and saving new events.

**Loading (Rehydration):**

1.  The repository retrieves the event stream for a given aggregate ID from the `EventStore`.
2.  It needs a way to create a new, empty instance of the correct aggregate type. Streak defines the `Streak\Domain\AggregateRoot\Factory` interface for this purpose.
    *   Each aggregate type requires a corresponding concrete implementation of this factory interface.
    *   The factory's `create(AggregateRoot\Id $id): AggregateRoot` method receives the ID and is responsible for instantiating the aggregate, injecting any necessary dependencies (like clocks, services) that the factory itself holds.
3.  The repository calls the `replay($stream)` method (from the `EventSourcing` trait) on the instance created by the factory, which applies the historical events to restore its state.

**Saving:**

1.  After a command is processed, the repository retrieves the uncommitted events from the aggregate instance using `$aggregate->events()` (from the `EventSourcing` trait).
2.  It passes these events to `EventStore->add(...$events)`.

**Generic Repositories:**

The combination of standardized, string-representable IDs (`AggregateRoot\Id` with `fromString`) and the **standardized `AggregateRoot\Factory` interface** enables the creation of highly **generic repositories**. A single repository service can potentially load *any* type of aggregate root if it knows how to:

a) Convert a string ID back into the correct `AggregateRoot\Id` object (`Id::fromString(...)`).
b) Locate and use the correct `AggregateRoot\Factory` implementation for the given aggregate type (often via a mapping or naming convention within the DI container), calling its `create($id)` method.

This significantly reduces persistence boilerplate compared to systems requiring a separate repository class for every aggregate type.

## Event Sourcing Implementation

In Event Sourcing, the state of an Aggregate Root is not stored directly. Instead, all changes to the aggregate are recorded as a sequence of immutable Domain Events. The current state is derived by replaying these events.

Streak provides traits to simplify implementing event-sourced aggregates:

*   `Streak\Domain\AggregateRoot\Identification`: Provides implementation for `id()` and identity comparison based on the ID.
*   `Streak\Domain\AggregateRoot\EventSourcing`: The core trait. It manages the sequence of uncommitted events, provides the `apply(Domain\Event $event)` method to record new events, and handles the mechanism for replaying events to rebuild state.
*   `Streak\Domain\AggregateRoot\Comparison`: Implements the `equals()` method based on Aggregate type and ID.

**The Pattern:**

1.  **Define State:** Add private properties to your Aggregate Root class to hold its state.
2.  **Implement `Event\Sourced\AggregateRoot`:** This interface combines `AggregateRoot` with event sourcing specific methods.
3.  **Use Traits:** Include `Identification`, `EventSourcing`, and `Comparison` traits.
4.  **Write Event Appliers:** For each Domain Event that can change the aggregate's state, create a private method named `apply<EventName>(Event $event)`. This method takes the specific event type as an argument and modifies the aggregate's private properties based on the event data.
    ```php
    private function applySomethingHappened(Events\SomethingHappened $event) : void
    {
        $this->someState = $event->getSomeData();
        $this->updatedAt = $event->occurredAt();
    }
    ```
5.  **Handle Commands (see below):** In your command handlers, after validating the command and business rules, call `$this->apply(new Events\SomethingHappened(...));` to record the change.

*(Note: While this documentation shows direct interface implementation and trait usage, you can create your own abstract base classes (e.g., `AbstractEventSourcedAggregate`) within your project that implement the required interfaces and use the necessary Streak traits. Your concrete aggregates can then extend these base classes to reduce boilerplate.)*

When the aggregate is loaded from the Event Store, the `EventSourcing` trait replays its historical events, calling the corresponding `apply<EventName>` methods to reconstruct the current state.

## Handling Commands

Commands represent requests to change the state of an aggregate.

*   **`Streak\Domain\CommandHandler`:** An interface indicating a class can handle commands.
*   **`Streak\Domain\Command\Handling`:** A trait that facilitates routing commands to specific handler methods within the class.

**The Pattern:**

1.  **Implement `CommandHandler`:** Add the interface to your Aggregate Root (or a dedicated command handler service).
2.  **Use Trait:** Include the `Command\Handling` trait.
3.  **Write Command Handlers:** For each command the aggregate should handle, create a public method named `handle<CommandName>(Command $command)`. This method takes the specific command type as an argument.
4.  **Implement Logic:** Inside the handler method:
    *   Validate the command data.
    *   Check business rules and invariants based on the aggregate's current state.
    *   If validation and rules pass, call `$this->apply(new DomainEvent(...));` to record the resulting change as one or more events.
    *   If rules are violated, throw a domain-specific exception.

```php
<?php

use Streak\Domain;
use Streak\Domain\Event;

final class MyAggregate implements Event\Sourced\AggregateRoot, Domain\CommandHandler
{
    use Domain\AggregateRoot\Identification;
    use Domain\AggregateRoot\EventSourcing;
    use Domain\AggregateRoot\Comparison;
    use Domain\Command\Handling;

    private $state;

    // Constructor usually takes the ID
    public function __construct(MyAggregate\Id $id)
    {
        $this->identifyBy($id);
    }

    // Command Handler
    public function handleDoSomething(Commands\DoSomething $command) : void
    {
        if ($this->state === 'invalid_state_for_command') {
            throw new Exception\CannotDoSomethingInThisState();
        }

        // Validation passed, apply the event
        $this->apply(new Events\SomethingHappened($this->id(), $command->data(), \DateTimeImmutable::create()));
    }

    // Event Applier
    private function applySomethingHappened(Events\SomethingHappened $event) : void
    {
        $this->state = 'updated_state';
        // ... update other properties
    }
}
```

This structure keeps the command processing logic and the state mutation logic separate but colocated within the Aggregate Root, ensuring it remains the guardian of its own consistency.

### Event-Sourced Entities within Aggregates

A powerful feature of Streak is that entities *within* an Aggregate Root can also be event-sourced. This allows complex sub-components of an aggregate to manage their own state changes through events, while still maintaining the overall consistency boundary of the Aggregate Root.

**Pattern:**

1.  **Implement `Event\Sourced\Entity`:** The entity class implements this interface.
2.  **Use Traits:** Include `Entity\Identification`, `Entity\EventSourcing`, and `Entity\Comparison` traits.
3.  **Define State & Appliers:** The entity has its own private state properties and `apply<EventName>` methods to mutate that state based on events *it* generates.
4.  **Apply Events:** Methods within the entity can call `$this->apply(new EntitySpecificEvent(...));` to record changes specific to that entity.
5.  **Register with Root:** Crucially, the entity needs to be linked to its Aggregate Root. This is typically done in the entity's constructor by calling `$this->registerAggregateRoot($aggregateRootInstance);` (method provided by the `Entity\EventSourcing` trait).

*(Note: Similar to Aggregate Roots, you can create abstract base classes for your event-sourced entities within your application to encapsulate the interface implementation and trait usage.)*

**How it Works:**

When an entity calls `$this->apply()`, the `Entity\EventSourcing` trait doesn't just store the event locally; it also registers the event with the Aggregate Root it was linked to via `registerAggregateRoot()`. When the Aggregate Root is saved (e.g., via `$repository->save($aggregateRoot)`), the root's `EventSourcing` trait collects not only the events applied directly to the root but also all events applied to its registered event-sourced entities.

This ensures that all changes within the aggregate boundary (whether originating from the root or a nested entity) are persisted atomically as a single sequence of events for that aggregate instance.

**Example:**

Imagine a `Project` aggregate containing multiple `Task` entities. A `Task` could be event-sourced:

```php
// Inside Task.php (implements Event\Sourced\Entity)
use Streak\Domain\Entity;

// ... traits ...

public function __construct(Project $project, Task\Id $id, ...) {
    $this->identifyBy($id);
    $this->registerAggregateRoot($project); // Link to parent
    // ...
}

public function completeTask(): void {
    if ($this->isCompleted) { return; }
    $this->apply(new Events\TaskCompleted($this->aggregateRootId(), $this->id(), ...));
}

private function applyTaskCompleted(Events\TaskCompleted $event): void {
    $this->isCompleted = true;
}
```

When `$projectRepository->save($project)` is called, the `TaskCompleted` event applied by the `Task` instance will be included in the list of events saved for the `Project`.

This allows for rich, encapsulated behavior within aggregates while maintaining the principles of event sourcing.

## Aggregate Snapshotting (Performance Optimization)

For aggregates with very long event histories, replaying all events every time the aggregate is loaded can become a performance bottleneck. **Snapshotting** is an optimization technique to mitigate this.

A snapshot captures the entire state of an aggregate at a specific version (or point in time). When loading an aggregate that has a snapshot:

1.  The repository first loads the most recent snapshot.
2.  It restores the aggregate's state *from the snapshot*.
3.  It then loads and replays only the events that occurred *after* the snapshot was taken.

This significantly reduces the number of events that need to be processed during rehydration.

### The `Snapshottable` Interface

Streak provides the `Streak\Domain\AggregateRoot\Snapshottable` interface for aggregates that support this optimization. It uses the **Memento pattern**:

```php
<?php

namespace Streak\Domain\AggregateRoot;

interface Snapshottable
{
    /**
     * Restores the aggregate's internal state from a previously created memento.
     *
     * @param array $memento The state representation (likely an associative array).
     */
    public function fromMemento(array $memento);

    /**
     * Creates a representation (memento) of the aggregate's current internal state.
     *
     * @return array The state representation (likely an associative array).
     */
    public function toMemento(): array;
}
```

**Implementation:**

*   An aggregate implementing `Snapshottable` needs to provide logic in `toMemento()` to gather all its critical state properties into an array.
*   It needs corresponding logic in `fromMemento()` to restore its properties from such an array.
*   The `EventSourcing` trait likely interacts with these methods when snapshotting is triggered.

### Snapshot Storage and Strategy

*   Snapshots need to be stored persistently, typically in a separate storage mechanism (e.g., a database table, document store) managed by a **Snapshot Store** service.
*   The strategy for *when* to take a snapshot (e.g., every N events, periodically) is usually configured or implemented within the persistence layer (e.g., a repository decorator or the Unit of Work).
*   The `StreakBundle` might provide configuration options for enabling snapshotting and configuring the snapshot store and strategy.

### Resetting Snapshots

If the structure of an aggregate's state changes or snapshots become corrupted, you might need to clear them. The `streak:snapshots:reset` console command ([see Console Commands](../symfony-bundle/console-commands.md)) is provided for this purpose, forcing aggregates to be rebuilt from their full event history on the next load.
