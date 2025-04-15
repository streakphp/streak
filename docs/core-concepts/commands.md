# Core Concepts: Commands

Commands represent an intent to change the system's state. They are imperative messages that tell the application to *do* something.

## What is a Command?

Unlike [Events](events.md) (which describe something that *has happened*), Commands describe something that *should happen*. 

Streak defines the core command interface:

```php
<?php

namespace Streak\Domain;

interface Command
{
}
```

Implementation example:

```php
<?php

namespace Domain\Project\Command;

use Streak\Domain\Command;

final class CreateProject implements Command
{
    public function __construct(
        public readonly string $id,
        public readonly string $name
    ) {
        if (empty($name)) {
            throw new \InvalidArgumentException('Project name cannot be empty');
        }
    }
}
```

Key characteristics:

*   **Imperative:** Use verbs in imperative mood (`CreateProject`, `RenameTask`)
*   **Data Carriers:** Contain all information needed for the action
*   **Targeted:** Route to a specific handler or aggregate
*   **Simple:** Just PHP objects implementing `Streak\Domain\Command`

## Command Handling

Command handlers execute the requested action through these steps:
1. Validate the command data
2. Check business rules
3. Make the changes
4. Record resulting events

Streak supports two approaches to handling commands:

### 1. Dedicated Command Handlers

A separate service class handles the command by implementing the `CommandHandler` interface:

```php
<?php

namespace Streak\Domain;

interface CommandHandler
{
    /**
     * @throws Exception\CommandNotSupported
     */
    public function handleCommand(Command $command): void;
}
```

Example implementation:

```php
<?php

namespace Domain\Project\Handler;

use Streak\Domain\CommandHandler;
use Streak\Domain\Command;
use Domain\Project\Command\CreateProject;
use Domain\Project\Project;
use Domain\Project\ProjectRepository;

final class CreateProjectHandler implements CommandHandler
{
    public function __construct(
        private ProjectRepository $repository
    ) {
    }

    public function handleCommand(Command $command): void
    {
        if (!$command instanceof CreateProject) {
            throw new \Streak\Domain\Exception\CommandNotSupported($command);
        }
        
        /** @var CreateProject $command */
        $project = new Project($command->id);
        $project->rename($command->name);
        
        $this->repository->add($project);
    }
}
```

### Using the Handling Trait

The same handler can be implemented more elegantly using the `Command\Handling` trait:

```php
<?php

namespace Domain\Project\Handler;

use Streak\Domain\CommandHandler;
use Streak\Domain\Command;
use Domain\Project\Command\CreateProject;
use Domain\Project\Project;
use Domain\Project\ProjectRepository;

final class CreateProjectHandler implements CommandHandler
{
    use Command\Handling;

    public function __construct(
        private ProjectRepository $repository
    ) {
    }

    public function handleCreateProject(CreateProject $command): void
    {
        $project = new Project($command->id);
        $project->rename($command->name);
        
        $this->repository->add($project);
    }
}
```

Using the trait automatically:
- Routes commands to type-specific handler methods
- Handles command type verification
- Throws appropriate exceptions for unsupported commands

## Handling Multiple Commands in a Single Handler

One of the biggest advantages of using the `Handling` trait is the ability to handle multiple command types in a single handler class:

```php
<?php

namespace Domain\Project\Handler;

use Streak\Domain\CommandHandler;
use Streak\Domain\Command;
use Domain\Project\Command\CreateProject;
use Domain\Project\Command\RenameProject;
use Domain\Project\Command\AddTask;
use Domain\Project\Project;
use Domain\Project\ProjectRepository;

final class ProjectCommandHandler implements CommandHandler
{
    use Command\Handling;

    public function __construct(
        private ProjectRepository $repository
    ) {
    }

    public function handleCreateProject(CreateProject $command): void 
    { /* ... */ }
    
    public function handleRenameProject(RenameProject $command): void 
    { /* ... */ }
    
    public function handleAddTask(AddTask $command): void 
    { /* ... */ }
}
```

This approach allows you to:
- Group related command handlers in a single class
- Maintain strong typing for each command type
- Automatically route commands to the correct handler method
- Avoid repetitive type checking and dispatch logic

### 2. Aggregate Root Handlers

The aggregate itself can handle commands. This requires:
1. Command implements `AggregateRootCommand`
2. Aggregate implements `CommandHandler`

The `AggregateRootCommand` interface:

```php
<?php

namespace Streak\Domain\Command;

use Streak\Domain\AggregateRoot;
use Streak\Domain\Command;

interface AggregateRootCommand extends Command
{
    public function aggregateRootId(): AggregateRoot\Id;
}
```

Example command implementation:

```php
<?php

namespace Domain\Project\Command;

use Streak\Domain\Command\AggregateRootCommand;
use Streak\Domain\AggregateRoot;
use Domain\Project\ProjectId;

final class CreateProject implements AggregateRootCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $name
    ) {
        if (empty($name)) {
            throw new \InvalidArgumentException('Project name cannot be empty');
        }
    }

    public function aggregateRootId(): AggregateRoot\Id
    {
        return new ProjectId($this->id);
    }
}
```

The aggregate implementation using the `Handling` trait:

```php
<?php

namespace Domain\Project;

use Streak\Domain\AggregateRoot;
use Streak\Domain\CommandHandler;
use Streak\Domain\Command;
use Domain\Project\Command\CreateProject;
use Domain\Project\Event\ProjectCreated;

final class Project implements AggregateRoot, CommandHandler
{
    use Command\Handling;
    
    private string $id;
    private string $name;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function handleCreateProject(CreateProject $command): void
    {
        $this->apply(new ProjectCreated($command->id, $command->name));
    }
    
    protected function applyProjectCreated(ProjectCreated $event): void
    {
        $this->name = $event->name;
    }
}
```

## Command Boundaries

Commands follow these important principles:

* **Single Aggregate:** Each command modifies exactly one aggregate
* **Atomic Storage:** All events from a command are stored together
* **Ordered Effects:** Side effects happen after event storage
* **Cross-Aggregate Changes:** Use [process managers](../tutorials/building-a-process-manager.md) or [sagas](../tutorials/building-a-saga.md)

These principles ensure:
* Clear boundaries of responsibility
* Strong consistency within aggregates
* Eventual consistency across aggregates
* Reliable event ordering

## Command Bus

The Command Bus routes commands to their handlers:

```php
<?php

namespace Streak\Application;

use Streak\Domain;
use Streak\Domain\Exception\CommandNotSupported;

interface CommandBus
{
    /**
     * @throws CommandNotSupported
     */
    public function dispatch(Domain\Command $command): void;
}
```

Usage example:

```php
<?php

use Domain\Project\Command\CreateProject;
use Streak\Application\CommandBus;

/** @var CommandBus $commandBus */
$command = new CreateProject('project-123', 'My Project');
$commandBus->dispatch($command);
```

For implementation details, see:
* [Building an Aggregate](../tutorials/building-an-aggregate.md) - Learn how to implement commands in your aggregates
