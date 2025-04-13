# Tutorial: Building an Event-Sourced Aggregate

This tutorial walks through creating a basic event-sourced Aggregate Root using Streak. We'll model a simple `Project` aggregate, inspired by the example found in the `todocler` application.

**Goal:** Create a `Project` aggregate that can be created, renamed, and have tasks added.

## 1. Define the ID

First, we need a unique identifier for our `Project`. IDs are Value Objects. We'll use a UUID-based ID.

```php
<?php

namespace App\Domain\Project;

use Streak\Domain;
use Streak\Domain\Id\UUID; // Assuming you have a UUID implementation

final class Id extends UUID implements Domain\AggregateRoot\Id
{
    // Inherits fromString() and toString() from UUID
    // Implements the AggregateRoot\Id marker interface
}
```

*   We extend a base `UUID` class (you might need `ramsey/uuid` or Symfony's UID component and a thin wrapper for this).
*   It implements `Streak\Domain\AggregateRoot\Id` to mark it specifically for aggregate roots.

## 2. Define Commands

Commands represent the actions we want to perform on the aggregate.

**CreateProject:**

```php
<?php

namespace App\Domain\Project\Command;

use Streak\Domain\Command;

final class CreateProject implements Command
{
    public function __construct(
        public readonly string $projectId,
        public readonly string $projectName,
        public readonly string $creatorId
    ) {}
}
```

**RenameProject:**

```php
<?php

namespace App\Domain\Project\Command;

use Streak\Domain\Command;

final class RenameProject implements Command
{
    public function __construct(
        public readonly string $projectId, // Target project
        public readonly string $newName,
        public readonly string $editorId
    ) {}
}
```

**AddTask:**

```php
<?php

namespace App\Domain\Project\Command;

use Streak\Domain\Command;

final class AddTask implements Command
{
    public function __construct(
        public readonly string $projectId, // Target project
        public readonly string $taskId,
        public readonly string $taskName,
        public readonly string $creatorId
    ) {}
}
```

*   These are simple POPOs implementing `Streak\Domain\Command`.
*   They carry the data needed for the action using `public readonly` properties.

## 3. Define Events

Events represent the state changes resulting from successful command execution.

**ProjectCreated:**

```php
<?php

namespace App\Domain\Project\Event;

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

**ProjectRenamed:**

```php
<?php

namespace App\Domain\Project\Event;

use Streak\Domain\Event;

final class ProjectRenamed implements Event
{
    public function __construct(
        public readonly string $projectId,
        public readonly string $newName,
        public readonly string $editorId,
        public readonly \DateTimeImmutable $editedAt
    ) {}
}
```

**TaskAdded:**

```php
<?php

namespace App\Domain\Project\Event;

use Streak\Domain\Event;

final class TaskAdded implements Event
{
    public function __construct(
        public readonly string $projectId,
        public readonly string $taskId,
        public readonly string $taskName,
        public readonly string $creatorId,
        public readonly \DateTimeImmutable $addedAt
    ) {}
}
```

*   Simple POPOs implementing `Streak\Domain\Event`.
*   Named in the past tense.
*   Contain data reflecting the change using `public readonly` properties.

## 4. Create the Aggregate Root

Now, let's build the `Project` aggregate root itself.

```php
<?php

namespace App\Domain\Project;

use App\Domain\Project\Command as Commands;
use App\Domain\Project\Event as Events;
// use App\Domain\Project\Exception as Exceptions; // Define custom exceptions
use Streak\Domain;
use Streak\Domain\Event;
use Webmozart\Assert\Assert; // Use an assertion library

final class Project implements Event\Sourced\AggregateRoot, Domain\CommandHandler
{
    // Import Streak traits for common functionality
    use Domain\AggregateRoot\Identification;
    use Domain\AggregateRoot\EventSourcing { apply as recordEvent; } // Alias apply to avoid conflict if needed
    use Domain\AggregateRoot\Comparison;
    use Domain\Command\Handling;

    // Internal state properties
    private string $name;
    private string $creatorId;
    private \DateTimeImmutable $createdAt;
    private array $tasks = []; // Store task IDs or value objects

    // Clock for timestamps (injected)
    private Domain\Clock $clock;

    // Constructor: Initialize with ID and dependencies
    public function __construct(Id $id, Domain\Clock $clock)
    {
        $this->identifyBy($id);
        $this->clock = $clock;
    }

    // --- Command Handlers ---

    public function handleCreateProject(Commands\CreateProject $command): void
    {
        // Ensure aggregate doesn't already exist
        if (null !== $this->firstEvent()) {
            throw new \LogicException('Project already created.'); // Or custom exception
        }
        Assert::uuid($command->creatorId, 'Creator ID must be a UUID.');
        Assert::notEmpty($command->projectName, 'Project name cannot be empty.');

        $this->recordEvent(new Events\ProjectCreated(
            $this->id()->toString(),
            $command->projectName,
            $command->creatorId,
            $this->clock->now()
        ));
    }

    public function handleRenameProject(Commands\RenameProject $command): void
    {
        // Check invariants/business rules
        Assert::uuid($command->editorId, 'Editor ID must be a UUID.');
        Assert::notEmpty($command->newName, 'New name cannot be empty.');
        if ($this->creatorId !== $command->editorId) { // Example rule: Only creator can rename
            throw new \DomainException('User not allowed to rename this project.'); // Custom exception
        }
        if ($this->name === $command->newName) {
            return; // No change needed
        }

        $this->recordEvent(new Events\ProjectRenamed(
            $this->id()->toString(),
            $command->newName,
            $command->editorId,
            $this->clock->now()
        ));
    }

    public function handleAddTask(Commands\AddTask $command): void
    {
        Assert::uuid($command->creatorId, 'Creator ID must be a UUID.');
        Assert::notEmpty($command->taskName, 'Task name cannot be empty.');
        if ($this->creatorId !== $command->creatorId) {
             throw new \DomainException('User not allowed to add tasks to this project.');
        }
        // Check if task already exists
        if (isset($this->tasks[$command->taskId])) { 
            throw new \DomainException('Task with this ID already exists in the project.');
        }

        $this->recordEvent(new Events\TaskAdded(
            $this->id()->toString(),
            $command->taskId,
            $command->taskName,
            $command->creatorId,
            $this->clock->now()
        ));
    }

    // --- Event Appliers (Mutators) ---

    private function applyProjectCreated(Events\ProjectCreated $event): void
    {
        $this->name = $event->projectName;
        $this->creatorId = $event->creatorId;
        $this->createdAt = $event->createdAt;
    }

    private function applyProjectRenamed(Events\ProjectRenamed $event): void
    {
        $this->name = $event->newName;
    }

    private function applyTaskAdded(Events\TaskAdded $event): void
    {
        // Store task ID or a simple Task value object
        // In a more complex scenario, Task itself could be an Event\Sourced\Entity
        // See Core Concepts: Aggregates - Event-Sourced Entities within Aggregates
        $this->tasks[$event->taskId] = $event->taskName; // Example: storing name by ID
    }
}

```

**Key Points:**

*   **Interfaces:** Implements `Event\Sourced\AggregateRoot` and `Domain\CommandHandler`.
*   **Traits:** Uses `Identification`, `EventSourcing`, `Comparison`, `Handling` for boilerplate.
*   **State:** Private properties hold the current state (`$name`, `$tasks`, etc.).
*   **Dependencies:** Injects `Clock` for reliable timestamps.
*   **Constructor:** Takes the `Id` and `Clock`. Calls `identifyBy()` from the `Identification` trait.
*   **Command Handlers (`handle*`)**: Contain business logic, validation (using `Assert`), and apply events using `$this->recordEvent()` (aliased from `$this->apply()` provided by the `EventSourcing` trait).
*   **Event Appliers (`apply*`)**: Private methods that mutate the aggregate's state based *only* on the data in the event. These are called by the `EventSourcing` trait.

## 5. Next Steps

*   **Repository:** Create a repository (often autowired if using the bundle) to load (`find`) and save (`store`/`add`) the aggregate using the `EventStore`.
*   **Dependency Injection:** Configure the `Clock` and potentially the repository in your DI container.
*   **Command Bus:** Dispatch commands (`CreateProject`, `RenameProject`, etc.) to the Command Bus, which will route them to the aggregate's handler methods.

This example provides a solid foundation for building your own event-sourced aggregates with Streak. 
