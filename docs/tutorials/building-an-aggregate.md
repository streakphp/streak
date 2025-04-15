# Building an Aggregate Root

This tutorial demonstrates how to build an Aggregate Root using Streak, starting with a simple implementation and evolving it to be event-sourced. We'll model a `Project` aggregate that can be created, renamed, and have tasks added.

**Goal:** Create a `Project` aggregate that can be created, renamed, and have tasks added, demonstrating the evolution from a simple aggregate to an event-sourced one with event-sourced entities.

## Step 1: Create a Simple Aggregate Root

Let's start with a simple aggregate root that implements the `Domain\AggregateRoot` interface but is not event-sourced. First, we need a unique identifier for our `Project`. IDs are Value Objects, and we'll use a UUID-based ID.

```php
<?php

namespace App\Domain\Project;

use Streak\Domain;
use Streak\Domain\Id\UUID; // Assuming you have a UUID implementation
use Webmozart\Assert\Assert;

// Project ID
final class Id extends UUID implements Domain\AggregateRoot\Id
{
    // Inherits fromString() and toString() from UUID
    // Implements the AggregateRoot\Id marker interface
}

final class Project implements Domain\AggregateRoot
{
    // Import Streak traits for common functionality
    use Domain\AggregateRoot\Identification;
    use Domain\AggregateRoot\Comparison;

    private string $name;
    private string $creatorId;
    private \DateTimeImmutable $createdAt;
    private array $tasks = []; // Simple array to store tasks

    public function __construct(
        Id $id,
        string $name,
        string $creatorId,
        \DateTimeImmutable $createdAt
    ) {
        $this->identifyBy($id);
        
        Assert::notEmpty($name, 'Project name cannot be empty.');
        Assert::uuid($creatorId, 'Creator ID must be a UUID.');

        $this->name = $name;
        $this->creatorId = $creatorId;
        $this->createdAt = $createdAt;
    }

    public function rename(string $newName, string $editorId): void
    {
        Assert::notEmpty($newName, 'New name cannot be empty.');
        Assert::uuid($editorId, 'Editor ID must be a UUID.');
        
        // Only allow the creator to rename the project
        if ($this->creatorId !== $editorId) {
            throw new \DomainException('User not allowed to rename this project.');
        }
        
        // Skip if the name hasn't changed
        if ($this->name === $newName) {
            return; // No change needed
        }

        $this->name = $newName;
    }

    public function addTask(string $taskId, string $taskName, string $creatorId): void
    {
        Assert::notEmpty($taskName, 'Task name cannot be empty.');
        Assert::uuid($creatorId, 'Creator ID must be a UUID.');
        
        // Only allow the creator to add tasks
        if ($this->creatorId !== $creatorId) {
            throw new \DomainException('User not allowed to add tasks to this project.');
        }
        
        // Check if task already exists
        if (isset($this->tasks[$taskId])) { 
            throw new \DomainException('Task with this ID already exists in the project.');
        }

        $this->tasks[$taskId] = $taskName;
    }
}
```

**Key Points:**
* We define a `Id` class that extends a base `UUID` class and implements `Domain\AggregateRoot\Id`
* The aggregate implements `Domain\AggregateRoot` interface
* Uses `Identification` and `Comparison` traits
* Directly mutates its state
* Contains validation logic in each method
* Enforces business rules (e.g., only creator can rename)
* Uses a simple array to store tasks
* At this points its persistence is developer responsibility

## Step 2: Add Entities to the Aggregate

Now, let's enhance our aggregate by adding a proper `Task` entity instead of using a simple array:

```php
<?php

namespace App\Domain\Project;

use Streak\Domain;
use Webmozart\Assert\Assert;

// Task ID
final class TaskId extends UUID implements Domain\Entity\Id
{
    // Inherits fromString() and toString() from UUID
    // Implements the Entity\Id marker interface
}

// Task entity
final class Task implements Domain\Entity
{
    // Import Streak traits for common functionality
    use Domain\Entity\Identification;
    use Domain\Entity\Comparison;

    private string $name;
    private string $creatorId;
    private \DateTimeImmutable $createdAt;

    public function __construct(
        TaskId $id,
        string $name,
        string $creatorId,
        \DateTimeImmutable $createdAt
    ) {
        $this->identifyBy($id);
        
        Assert::notEmpty($name, 'Task name cannot be empty.');
        Assert::uuid($creatorId, 'Creator ID must be a UUID.');

        $this->name = $name;
        $this->creatorId = $creatorId;
        $this->createdAt = $createdAt;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function creatorId(): string
    {
        return $this->creatorId;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}

// Project aggregate with Task entities
final class Project implements Domain\AggregateRoot
{
    // Import Streak traits for common functionality
    use Domain\AggregateRoot\Identification;
    use Domain\AggregateRoot\Comparison;

    private string $name;
    private string $creatorId;
    private \DateTimeImmutable $createdAt;
    private array $tasks = []; // Array of Task entities

    public function __construct(
        Id $id,
        string $name,
        string $creatorId,
        \DateTimeImmutable $createdAt
    ) {
        $this->identifyBy($id);
        
        Assert::notEmpty($name, 'Project name cannot be empty.');
        Assert::uuid($creatorId, 'Creator ID must be a UUID.');

        $this->name = $name;
        $this->creatorId = $creatorId;
        $this->createdAt = $createdAt;
    }

    public function rename(string $newName, string $editorId): void
    {
        Assert::notEmpty($newName, 'New name cannot be empty.');
        Assert::uuid($editorId, 'Editor ID must be a UUID.');
        
        // Only allow the creator to rename the project
        if ($this->creatorId !== $editorId) {
            throw new \DomainException('User not allowed to rename this project.');
        }
        
        // Skip if the name hasn't changed
        if ($this->name === $newName) {
            return; // No change needed
        }

        $this->name = $newName;
    }

    public function addTask(string $taskId, string $taskName, string $creatorId): void
    {
        Assert::notEmpty($taskName, 'Task name cannot be empty.');
        Assert::uuid($creatorId, 'Creator ID must be a UUID.');
        
        // Only allow the creator to add tasks
        if ($this->creatorId !== $creatorId) {
            throw new \DomainException('User not allowed to add tasks to this project.');
        }
        
        // Check if task already exists
        if (isset($this->tasks[$taskId])) { 
            throw new \DomainException('Task with this ID already exists in the project.');
        }

        $this->tasks[$taskId] = new Task(
            TaskId::fromString($taskId),
            $taskName,
            $creatorId,
            new \DateTimeImmutable()
        );
    }
}
```

**Key Changes:**
* Added a proper `TaskId` class that implements `Domain\Entity\Id`
* Task entity now uses `Identification` and `Comparison` traits from `Domain\Entity`
* Task entity properly identifies itself with a `TaskId` object
* Project aggregate now creates Task entities with proper `TaskId` objects

## Step 3: Define Events

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

## Step 4: Make the Aggregate Event-Sourced

Now, let's convert our aggregate to an event-sourced one, but keep the entities non-event-sourced:

```php
<?php

namespace App\Domain\Project;

use App\Domain\Project\Event as Events;
use Streak\Domain;
use Streak\Domain\Event;
use Webmozart\Assert\Assert;

// Event-sourced Project aggregate
final class Project implements Event\Sourced\AggregateRoot
{
    // Import Streak traits for common functionality
    use Domain\AggregateRoot\Identification;
    use Domain\AggregateRoot\EventSourcing;
    use Domain\AggregateRoot\Comparison;

    private string $name;
    private string $creatorId;
    private \DateTimeImmutable $createdAt;
    private array $tasks = []; // Array of Task entities

    // Clock for timestamps (injected)
    private Domain\Clock $clock;

    // Constructor: Initialize with ID and dependencies
    public function __construct(Id $id, Domain\Clock $clock)
    {
        $this->identifyBy($id);
        $this->clock = $clock;
    }

    public function rename(string $newName, string $editorId): void
    {
        // Check invariants/business rules
        Assert::uuid($editorId, 'Editor ID must be a UUID.');
        Assert::notEmpty($newName, 'New name cannot be empty.');
        
        // Only allow the creator to rename the project
        if ($this->creatorId !== $editorId) {
            throw new \DomainException('User not allowed to rename this project.');
        }
        
        // Skip if the name hasn't changed
        if ($this->name === $newName) {
            return; // No change needed
        }

        $this->apply(new Events\ProjectRenamed(
            $this->id()->toString(),
            $newName,
            $editorId,
            $this->clock->now()
        ));
    }

    public function addTask(string $taskId, string $taskName, string $creatorId): void
    {
        Assert::uuid($creatorId, 'Creator ID must be a UUID.');
        Assert::notEmpty($taskName, 'Task name cannot be empty.');
        
        // Only allow the creator to add tasks
        if ($this->creatorId !== $creatorId) {
             throw new \DomainException('User not allowed to add tasks to this project.');
        }
        
        // Check if task already exists
        if (isset($this->tasks[$taskId])) { 
            throw new \DomainException('Task with this ID already exists in the project.');
        }

        $this->apply(new Events\TaskAdded(
            $this->id()->toString(),
            $taskId,
            $taskName,
            $creatorId,
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
        // Create a new Task entity
        $this->tasks[$event->taskId] = new Task(
            TaskId::fromString($event->taskId),
            $event->taskName,
            $event->creatorId,
            $event->addedAt
        );
    }
}
```

**Key Changes:**
* Aggregate now implements `Event\Sourced\AggregateRoot` interface
* Uses `EventSourcing` trait without an alias
* Methods now record events instead of directly mutating state
* State changes happen in event applier methods
* Added `Clock` dependency for reliable timestamps
* Constructor now takes `Id` and `Clock` instead of all properties
* Task entity remains non-event-sourced

## Step 5: Make the Task Entity Event-Sourced

Now, let's make the Task entity event-sourced as well:

```php
<?php

namespace App\Domain\Project;

use App\Domain\Project\Event as Events;
use Streak\Domain;
use Streak\Domain\Event;
use Webmozart\Assert\Assert;

// Task entity (event-sourced)
final class Task implements Event\Sourced\Entity
{
    // Import Streak traits for common functionality
    use Domain\AggregateRoot\Identification;
    use Domain\AggregateRoot\EventSourcing;
    use Domain\AggregateRoot\Comparison;

    private string $name;
    private string $creatorId;
    private \DateTimeImmutable $createdAt;

    // Clock for timestamps (injected)
    private Domain\Clock $clock;

    // Constructor: Initialize with ID and dependencies
    public function __construct(string $id, Domain\Clock $clock)
    {
        $this->identifyBy($id);
        $this->clock = $clock;
    }

    // --- Public Methods ---

    public function create(string $name, string $creatorId): void
    {
        // Ensure entity doesn't already exist
        if (null !== $this->firstEvent()) {
            throw new \LogicException('Task already created.');
        }
        
        // Add validation
        Assert::uuid($creatorId, 'Creator ID must be a UUID.');
        Assert::notEmpty($name, 'Task name cannot be empty.');

        $this->apply(new Events\TaskCreated(
            $this->id()->toString(),
            $name,
            $creatorId,
            $this->clock->now()
        ));
    }

    // --- Event Appliers (Mutators) ---

    private function applyTaskCreated(Events\TaskCreated $event): void
    {
        $this->name = $event->taskName;
        $this->creatorId = $event->creatorId;
        $this->createdAt = $event->createdAt;
    }

    // --- Getters ---

    public function name(): string
    {
        return $this->name;
    }

    public function creatorId(): string
    {
        return $this->creatorId;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}

// Event-sourced Project aggregate with event-sourced Task entities
final class Project implements Event\Sourced\AggregateRoot
{
    // Import Streak traits for common functionality
    use Domain\AggregateRoot\Identification;
    use Domain\AggregateRoot\EventSourcing;
    use Domain\AggregateRoot\Comparison;

    private string $name;
    private string $creatorId;
    private \DateTimeImmutable $createdAt;
    private array $tasks = []; // Array of Task entities

    // Clock for timestamps (injected)
    private Domain\Clock $clock;

    // Constructor: Initialize with ID and dependencies
    public function __construct(Id $id, Domain\Clock $clock)
    {
        $this->identifyBy($id);
        $this->clock = $clock;
    }

    public function rename(string $newName, string $editorId): void
    {
        // Check invariants/business rules
        Assert::uuid($editorId, 'Editor ID must be a UUID.');
        Assert::notEmpty($newName, 'New name cannot be empty.');
        
        // Only allow the creator to rename the project
        if ($this->creatorId !== $editorId) {
            throw new \DomainException('User not allowed to rename this project.');
        }
        
        // Skip if the name hasn't changed
        if ($this->name === $newName) {
            return; // No change needed
        }

        $this->apply(new Events\ProjectRenamed(
            $this->id()->toString(),
            $newName,
            $editorId,
            $this->clock->now()
        ));
    }

    public function addTask(string $taskId, string $taskName, string $creatorId): void
    {
        Assert::uuid($creatorId, 'Creator ID must be a UUID.');
        Assert::notEmpty($taskName, 'Task name cannot be empty.');
        
        // Only allow the creator to add tasks
        if ($this->creatorId !== $creatorId) {
             throw new \DomainException('User not allowed to add tasks to this project.');
        }
        
        // Check if task already exists
        if (isset($this->tasks[$taskId])) { 
            throw new \DomainException('Task with this ID already exists in the project.');
        }

        $this->apply(new Events\TaskAdded(
            $this->id()->toString(),
            $taskId,
            $taskName,
            $creatorId,
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
        // Create a new event-sourced Task entity
        $task = new Task($event->taskId, $this->clock);
        $task->create($event->taskName, $event->creatorId);
        
        // Store the task entity
        $this->tasks[$event->taskId] = $task;
    }
}
```

**Key Changes:**
* Task entity now implements `Event\Sourced\Entity` interface
* Task entity uses `EventSourcing` trait
* Task entity has its own events and event appliers
* Project aggregate now creates event-sourced Task entities
* Added `TaskCreated` event (not shown in the events section for brevity)

## Step 6: Define Commands

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

## Step 7: Create Command Handlers

Now, let's create command handlers that will load the aggregate and call the appropriate methods:

```php
<?php

namespace App\Domain\Project;

use App\Domain\Project\Command as Commands;
use Streak\Domain\Command;
use Streak\Domain\CommandHandler;
use Streak\Domain\Clock;
use Streak\Domain\AggregateRoot\Repository;

final class CreateProjectHandler implements CommandHandler
{
    public function __construct(
        private Repository $repository,
        private Clock $clock
    ) {
    }

    public function handle(Commands\CreateProject $command): void
    {
        $project = new Project(
            Id::fromString($command->projectId),
            $this->clock
        );
        
        // Call the appropriate method on the aggregate
        $project->create($command->projectName, $command->creatorId);
        
        // Add the aggregate to the repository (only for new aggregates)
        $this->repository->add($project);
    }
}

final class RenameProjectHandler implements CommandHandler
{
    public function __construct(
        private Repository $repository
    ) {
    }

    public function handle(Commands\RenameProject $command): void
    {
        // Load the aggregate from the repository
        $project = $this->repository->find(Id::fromString($command->projectId));
        if (null === $project) {
            throw new \DomainException('Project not found.');
        }
        
        // Call the appropriate method on the aggregate
        $project->rename($command->newName, $command->editorId);
        
        // No need to save - the repository automatically tracks changes
    }
}

final class AddTaskHandler implements CommandHandler
{
    public function __construct(
        private Repository $repository
    ) {
    }

    public function handle(Commands\AddTask $command): void
    {
        // Load the aggregate from the repository
        $project = $this->repository->find(Id::fromString($command->projectId));
        if (null === $project) {
            throw new \DomainException('Project not found.');
        }
        
        // Call the appropriate method on the aggregate
        $project->addTask($command->taskId, $command->taskName, $command->creatorId);
        
        // No need to save - the repository automatically tracks changes
    }
}
```

**Key Points:**
* Each command has its own handler class
* Handlers implement the `CommandHandler` interface
* Handlers use Streak's `Repository` interface, not a custom repository
* The repository handles ID type checking and aggregate creation
* For new aggregates, use `add()` method (only used once)
* For existing aggregates, use `find()` method to load them
* The `find()` method returns null if the aggregate doesn't exist
* No need to explicitly save changes - the repository automatically tracks them
* Uses the static factory method to create the aggregate

## Step 8: Make the Aggregate Handle Commands Directly

Finally, let's modify our aggregate to handle commands directly:

```php
<?php

namespace App\Domain\Project;

use App\Domain\Project\Command as Commands;
use App\Domain\Project\Event as Events;
use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\CommandHandler;
use Webmozart\Assert\Assert;

// Event-sourced Project aggregate with event-sourced Task entities and command handling
final class Project implements Event\Sourced\AggregateRoot, CommandHandler
{
    // Import Streak traits for common functionality
    use Domain\AggregateRoot\Identification;
    use Domain\AggregateRoot\EventSourcing;
    use Domain\AggregateRoot\Comparison;
    use Domain\Command\Handling;

    private string $name;
    private string $creatorId;
    private \DateTimeImmutable $createdAt;
    private array $tasks = []; // Array of Task entities

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
            throw new \LogicException('Project already created.');
        }
        
        // Add validation
        Assert::uuid($command->creatorId, 'Creator ID must be a UUID.');
        Assert::notEmpty($command->projectName, 'Project name cannot be empty.');

        $this->apply(new Events\ProjectCreated(
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
        
        // Only allow the creator to rename the project
        if ($this->creatorId !== $command->editorId) {
            throw new \DomainException('User not allowed to rename this project.');
        }
        
        // Skip if the name hasn't changed
        if ($this->name === $command->newName) {
            return; // No change needed
        }

        $this->apply(new Events\ProjectRenamed(
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
        
        // Only allow the creator to add tasks
        if ($this->creatorId !== $command->creatorId) {
             throw new \DomainException('User not allowed to add tasks to this project.');
        }
        
        // Check if task already exists
        if (isset($this->tasks[$command->taskId])) { 
            throw new \DomainException('Task with this ID already exists in the project.');
        }

        $this->apply(new Events\TaskAdded(
            $this->id()->toString(),
            $command->taskId,
            $command->taskName,
            $command->creatorId,
            $this->clock->now()
        ));
    }

    // Event appliers are defined in previous steps
}
```

**Key Changes:**
* Aggregate now implements `CommandHandler` interface
* Uses `Domain\Command\Handling` trait
* Replaces public methods with command handlers
* Command handlers directly process commands and record events
* Event appliers remain the same as in previous steps

## Step 9: Next Steps

*   **Dependency Injection:** Configure the `Clock`, `EventStore`, and repository in your DI container.
*   **Command Bus:** Dispatch commands (`CreateProject`, `RenameProject`, etc.) to the Command Bus, which will route them to the aggregate's handler methods.

This example demonstrates the evolution from a simple aggregate to an event-sourced one with event-sourced entities, and shows how to handle commands directly. Each step builds on the previous one, making it easier to understand the concepts and patterns involved. 
