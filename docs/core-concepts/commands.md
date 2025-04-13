# Core Concepts: Commands

Commands represent an intent or a request to change the state of the system. They are imperative messages that tell the application to *do* something.

## What is a Command?

Unlike Events (which describe something that *has happened*), Commands describe something that *should happen*. Examples include `RegisterUser`, `PlaceOrder`, `CreateProject`, `RenameTask`.

Key characteristics of Commands:

*   **Imperative:** Named using verbs in the imperative mood (e.g., `CreateProject`).
*   **Data Carriers:** Contain all the necessary information required to execute the requested action (e.g., user details for `RegisterUser`, project name for `CreateProject`).
*   **Targeted (Implicitly):** While a command object itself might not always contain the target aggregate ID directly, it's usually routed to a specific Aggregate instance or handler responsible for that domain concept.

In Streak, Commands are typically simple PHP objects (POPOs) that implement the marker interface `Streak\Domain\Command`.

```php
<?php

namespace My\Domain\Commands;

use Streak\Domain\Command;

final class CreateProject implements Command
{
    public function __construct(
        public readonly string $projectId, // ID for the new project
        public readonly string $projectName,
        public readonly string $creatorId
    ) {}
}
```

## Command Handlers

A Command Handler is responsible for receiving a command and orchestrating the execution of the requested action. In event-sourced systems using Streak, this often involves:

1.  Loading the relevant Aggregate Root from the Event Store.
2.  Invoking a method on the Aggregate Root, passing the command data.
3.  Saving the new events produced by the Aggregate Root back to the Event Store.

Streak supports two main approaches for command handling:

1.  **Aggregate Roots as Handlers:** The Aggregate Root itself implements `Streak\Domain\CommandHandler` and uses the `Streak\Domain\Command\Handling` trait. This trait enables the `CommandBus` to route commands directly to public methods on the aggregate instance matching the convention `handle<CommandName>`. These methods contain the logic to process the command and apply resulting events, keeping the decision-making logic close to the state it affects. **When using this pattern, the `CommandBus` effectively routes the command directly to the specific aggregate instance identified as the handler.**

    ```php
    // Inside an Aggregate Root class
    use Streak\Domain\Command\Handling;

    public function handleCreateProject(Commands\CreateProject $command): void
    {
        // Validation & Business Logic
        if (empty($command->projectName())) {
            throw new InvalidArgumentException('Project name cannot be empty.');
        }

        $this->apply(new Events\ProjectCreated(...));
    }
    ```

2.  **Dedicated Command Handler Services:** A separate service class implements `Streak\Domain\CommandHandler` (often specific to a command type or an aggregate type). This handler service would typically depend on an `AggregateRoot\Repository` (or directly on the `EventStore`) to load/save the aggregate and then call a public method on the loaded aggregate.

    ```php
    <?php

    namespace My\Application\CommandHandlers;

    use Streak\Domain;
    use My\Domain\Project;
    use My\Domain\Commands;

    final class CreateProjectHandler implements Domain\CommandHandler
    {
        public function __construct(private Project\Repository $repository) {}

        public function __invoke(Commands\CreateProject $command): void
        {
            // Note: In event sourcing, creation might mean instantiating
            // a new aggregate and then calling its command handler method.
            // Or, a factory could be used.
            $project = Project::create($command->projectId(), $command->creatorId()); // Example static factory
            $project->rename($command->projectName()); // Call method on aggregate

            $this->repository->save($project);
        }
    }
    ```

    The choice between these depends on complexity and preference. Aggregates as handlers are simpler for straightforward cases. Dedicated handlers can be better if command handling involves external services or complex orchestration.

## Command Bus

To decouple the command sender from the command handler, a Command Bus is typically used. The sender dispatches a command object to the bus, and the bus ensures it gets delivered to the correct registered handler.

Streak defines the `Streak\Application\CommandBus` interface:

```php
<?php

namespace Streak\Application;

use Streak\Domain\Command;
use Streak\Domain\CommandHandler;

interface CommandBus
{
    // Registers a handler for a specific type of command
    public function register(CommandHandler $handler, Command $command):

    // Dispatches a command to its registered handler
    public function dispatch(Command $command): void;
}
```

*Note: Features like automatic command retries or transaction management (e.g., wrapping handler execution in a database transaction) are typically handled by specific bus implementations, middleware, decorators, or integrated framework features (like Symfony Messenger), rather than being built into the core `CommandBus` interface itself.*

Streak provides implementations (often found in the `Infrastructure/Application` layer or provided by the `StreakBundle`), such as:

*   A simple synchronous command bus.
*   Buses that integrate with message queues (though not explicitly in the core library provided interfaces shown).

The `StreakBundle` typically handles automatically registering command handlers (found via interfaces or attributes) with the configured command bus service. 
