# Tutorial: Handling Commands & Events

This tutorial builds upon the [Building an Aggregate](./building-an-aggregate.md) tutorial and demonstrates the flow of handling a command, producing an event, and reacting to that event with a listener.

**Goal:** Implement the command handling logic for `CreateProject` and create a simple listener that logs when a project is created.

## Prerequisites

*   Completed the [Building an Aggregate](./building-an-aggregate.md) tutorial (having the `Project` aggregate and `ProjectCreated` event).
*   An [Event Store](../core-concepts/event-store.md) configured (e.g., `InMemoryEventStore` or a persistent one).
*   A [Command Bus](../core-concepts/command-bus.md) configured.
*   An [Event Bus](../core-concepts/event-bus.md) configured (often used with `PublishingEventStore`).
*   (Optional) A logger instance (PSR-3 compatible) available via dependency injection.

## Steps

**1. Define the Command:**

First, ensure you have the command defined. A command is a simple DTO representing the intent.

```php
<?php

namespace App\Application\Command;

use Streak\Domain\Id\UUID; // Or your chosen ID implementation

final class CreateProject
{
    public readonly string $projectId;
    public readonly string $projectName;

    public function __construct(?string $projectId = null, string $projectName)
    {
        $this->projectId = $projectId ?? UUID::random()->toString();
        $this->projectName = $projectName;
    }
}
```

**2. Implement the Command Handler:**

Create a handler responsible for processing the `CreateProject` command. It will typically interact with the Aggregate (in this case, creating a new `Project`) and the `EventStore`.

```php
<?php

namespace App\Application\CommandHandler;

use App\Application\Command\CreateProject;
use App\Domain\Project; // Your aggregate root class
use App\Domain\ProjectId; // Your aggregate ID class
use Streak\Application\CommandHandler;
use Streak\Application\CommandHandler\Handling; // Optional convenience trait
use Streak\Domain\EventStore;

/**
 * Handles the CreateProject command.
 * Creates a new Project aggregate and stores its initial event.
 */
final class CreateProjectHandler implements CommandHandler
{
    use Handling; // Provided by Streak\Application\CommandHandling

    public function __construct(private EventStore $eventStore)
    {
    }

    public function handleCreateProject(CreateProject $command): void
    {
        // 1. Create the aggregate instance
        $id = new ProjectId($command->projectId);
        $project = Project::create($id, $command->projectName);

        // 2. Add the aggregate (with its uncommitted events) to the event store
        // The Event Store will persist the events (e.g., ProjectCreated)
        $this->eventStore->add($project);

        // Note: If using PublishingEventStore, the event will also be
        // published to the Event Bus automatically *after* successful persistence.
    }
}
```
*   The handler implements `Streak\Application\CommandHandler`.
*   We use the optional `Handling` trait for convention-based method naming (`handle<CommandName>`).
*   It receives the `EventStore` via dependency injection.
*   The `handleCreateProject` method:
    *   Creates a new `Project` aggregate using the static factory method defined in the aggregate tutorial.
    *   Calls `$this->eventStore->add($project)`. This persists the aggregate's initial event (`ProjectCreated`) to the store.

**3. Register the Command Handler:**

Ensure the `CreateProjectHandler` is registered as a service and tagged appropriately so the `CommandBus` can find it. If using the [Streak Symfony Bundle](../symfony-bundle/) with autoconfiguration, implementing `CommandHandler` is usually sufficient.

```yaml
# config/services.yaml (Example for Symfony)
services:
    # ... other services

    App\Application\CommandHandler\CreateProjectHandler:
        arguments: ['@Streak\Domain\EventStore']
        # Autoconfiguration typically adds the 'streak.command_handler' tag
```

**4. Create an Event Listener:**

We need a listener to react to the `ProjectCreated` event, perhaps to update a read model or send a notification. This listener implements `Streak\Domain\Event\Listener`.

```php
<?php

namespace App\Application\Listener;

use App\Domain\Project\Event\ProjectCreated;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Listener\Id;
use Streak\Domain\Id\UUID;
use Psr\Log\LoggerInterface; // Example dependency

// Define a unique ID class for this listener instance
class ProjectCreationLoggerId extends UUID implements Listener\Id {}

final class ProjectCreationLogger implements Listener, Listener\Id
{
    use Event\Listener\Identifying; // Trait for ID handling
    // Using Listening trait for on<EventName> convention
    use Event\Listener\Listening;

    public function __construct(
        private LoggerInterface $logger,
        ?Listener\Id $id = null
    ) {
        // Assign a default random ID if none provided
        $this->identifyBy($id ?? new ProjectCreationLoggerId(UUID::random()->toString()));
    }

    public function listenerId(): Listener\Id
    {
        return $this->id;
    }

    /**
     * Handle the ProjectCreated event.
     * This method is called by the Listening trait's on() method.
     */
    public function onProjectCreated(ProjectCreated $event): void
    {
        $this->logger->info(sprintf(
            'Project created: ID=%s, Name=%s',
            $event->projectId->toString(), // Assuming ProjectId has toString()
            $event->initialName
        ));
    }
}
```
*   The listener implements `Listener` and `Listener\Id`.
*   It uses the `Identifying` trait for ID management and `Listening` for the `on<EventName>` convention.
*   It injects a `LoggerInterface`.
*   The `onProjectCreated` method logs information from the event.

**5. Register the Event Listener:**

The listener needs to be registered so the `EventBus` (if used) or the `Subscription` mechanism can find it.

*   **For Event Bus:** If using the `EventBus` (often via `PublishingEventStore`), register the listener as a service. With the [Streak Symfony Bundle](../symfony-bundle/) and autoconfiguration, implementing `Listener` might be enough for the bus to pick it up.
*   **For Subscriptions:** If this listener needs to run as a persistent subscription (e.g., for projection), you also need a `Listener\Factory` (see [Building a Projection](./building-a-projection.md)) and register that factory service (tagging it `streak.listener_factory` in Symfony).

```yaml
# config/services.yaml (Example for Symfony)
services:
    # ... other services

    App\Application\Listener\ProjectCreationLogger:
        arguments: ['@Psr\Log\LoggerInterface']
        # Autoconfiguration likely detects Listener implementation
        # If running as a subscription, register the Factory instead:
    # App\Application\Listener\ProjectCreationLoggerFactory:
    #     arguments: ['@Psr\Log\LoggerInterface']
    #     tags: ['streak.listener_factory']
```

**6. Dispatch the Command:**

Finally, dispatch the command using the `CommandBus` from somewhere in your application (e.g., a controller, an API endpoint handler, another service).

```php
<?php

// Somewhere in your application (e.g., Symfony Controller)
use App\Application\Command\CreateProject;
use Streak\Application\CommandBus;
use Symfony\Component\HttpFoundation\Response;

class ProjectController // ...
{
    public function createAction(CommandBus $commandBus): Response
    {
        $command = new CreateProject(projectName: 'My New Project');

        try {
            $commandBus->dispatch($command);
            // Command processed successfully
            return new Response('Project created with ID: ' . $command->projectId);
        } catch (\Throwable $e) {
            // Handle potential errors during command processing
            return new Response('Error creating project: ' . $e->getMessage(), 500);
        }
    }
}
```

## Flow Summary

1.  Code dispatches `CreateProject` command to the `CommandBus`.
2.  `CommandBus` finds `CreateProjectHandler`.
3.  `CreateProjectHandler::handleCreateProject` is called.
4.  Handler creates a new `Project` aggregate instance (which records `ProjectCreated` internally).
5.  Handler calls `eventStore->add($project)`.
6.  `EventStore` persists the `ProjectCreated` event.
7.  If using `PublishingEventStore`, it now calls `eventBus->publish($eventEnvelope)`.
8.  `EventBus` finds the registered `ProjectCreationLogger` listener.
9.  `EventBus` invokes `ProjectCreationLogger::on($eventEnvelope)` (which the `Listening` trait routes to `onProjectCreated`).
10. `ProjectCreationLogger::onProjectCreated` executes, logging the message.

This demonstrates the fundamental CQRS/ES flow: Command -> Handler -> Aggregate -> Event -> Store -> Event Bus -> Listener.
