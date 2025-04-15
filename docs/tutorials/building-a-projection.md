# Building a Projection (Read Model)

This tutorial demonstrates how to create a Projection (also known as a Read Model) using Streak. Projections listen to domain events and build a specific data representation optimized for querying.
In Streak, projections are implemented as a specialized type of [Event Listener](../core-concepts/listeners.md).

**Goal:** Create a `project_summary` database table that stores the ID and name of projects, kept up-to-date by listening to `ProjectCreated`, `ProjectRenamed`, and `ProjectArchived` events.

## Step 1: Create the Listener

First, we'll create the `ProjectSummaryProjector` class. This class will implement the `Streak\Domain\Event\Listener` interface to receive events and `Streak\Domain\Event\Listener\Id` to give itself a unique identity within the system. We'll start by handling events manually within the `on()` method.

**1. Create the Listener Class:**

```php
<?php

namespace App\Application\Projector;

use App\Domain\Project\Event\ProjectCreated;
use App\Domain\Project\Event\ProjectRenamed;
use App\Domain\Project\Event\ProjectArchived;
use Doctrine\DBAL\Connection;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;

// Note: We also implement Listener\Id here, explained below.
final class ProjectSummaryProjector implements Listener, Listener\Id
{
    private const TABLE_NAME = 'project_summary';

    // Store the unique ID for this listener instance
    private readonly Listener\Id $id;

    public function __construct(
        private Connection $connection,
        // Inject the specific ID instance (explained below)
        ProjectSummaryProjectorId $id
    ) {
        $this->id = $id;
    }

    /**
     * Required by the Listener interface. Returns the listener's unique ID.
     */
    public function id(): Listener\Id
    {
        return $this->id;
    }

    /**
     * Required by the Listener interface. Processes incoming event envelopes.
     */
    public function on(Event\Envelope $envelope): bool
    {
        $event = $envelope->message();

        // Check the event type and perform database actions
        if ($event instanceof ProjectCreated) {
            $this->connection->insert(self::TABLE_NAME, [
                'project_id' => (string) $event->projectId, // Assuming projectId has __toString()
                'project_name' => $event->initialName,
            ]);
            return true; // Indicate the event was handled
        }

        if ($event instanceof ProjectRenamed) {
            $this->connection->update(
                self::TABLE_NAME,
                ['project_name' => $event->newName],
                ['project_id' => (string) $event->projectId]
            );
            return true; // Indicate the event was handled
        }

        if ($event instanceof ProjectArchived) {
            $this->connection->delete(
                self::TABLE_NAME,
                ['project_id' => (string) $event->projectId]
            );
            return true; // Indicate the event was handled
        }

        // Ignore events this listener doesn't care about
        return false;
    }
}

```

*   Implements `Listener` and `Listener\Id`.
*   Defines the target table name (`TABLE_NAME`).
*   Injects a Doctrine `Connection` for database operations.
*   Injects a `ProjectSummaryProjectorId` (its specific ID implementation, covered next).
*   Stores the injected ID in a `private readonly` property.
*   Implements `id()` to return the stored ID.
*   Implements `on()` with manual `instanceof` checks and corresponding DBAL calls. It returns `true` if an event is processed, `false` otherwise, allowing event buses to know if the event was handled.

**2. Define the Listener ID:**

Every `Listener` in Streak must be identifiable by implementing `Listener\Id`. This allows the framework (especially event subscriptions) to track and manage listener instances reliably.

Since our `ProjectSummaryProjector` often acts as a singleton (only one instance is needed), we create a specific ID class for it.

```php
<?php

namespace App\Application\Projector;

use Streak\Domain\Event\Listener;
use Streak\Domain\Id; // Base ID interface
use Streak\Domain\Id\UUID; // Can be based on UUIDs or other strategies

// This ID uniquely identifies our ProjectSummaryProjector instance
final class ProjectSummaryProjectorId implements Listener\Id
{
    // A fixed, known UUID for this singleton projector
    private const ID = 'a1a5c5a5-4122-48f6-a673-4e176a61f8f8';

    public function equals(Id $id): bool
    {
        // For a singleton, we only need to check if the other ID is the same class
        return $id instanceof self;
    }

    public static function fromString(string $id): Id
    {
        // Ensure only the predefined ID string can create this ID object
        if ($id !== self::ID) {
            throw new \InvalidArgumentException('Invalid ID string for ProjectSummaryProjectorId');
        }
        return new self();
    }

    public function toString(): string
    {
        return self::ID;
    }
}

```

*   It implements `Listener\Id`.
*   We use a hardcoded UUID string (`self::ID`) because this projection is treated as a singleton. If you needed multiple instances of a projector type (e.g., one per user), you would typically use dynamic UUIDs generated via `UUID::random()`.
*   `equals`, `fromString`, and `toString` are implemented to work with this fixed ID. The `ProjectSummaryProjector` constructor receives an instance of this class.

**3. Prepare the Database Table:**

Ensure the `project_summary` table exists in your database. You might use a Doctrine migration or execute SQL directly:

```sql
CREATE TABLE project_summary (
    project_id VARCHAR(36) PRIMARY KEY NOT NULL, -- UUID length
    project_name VARCHAR(255) NOT NULL
);
```

At this point, we have a basic, identifiable listener capable of updating the `project_summary` table. However, the `on()` method can become complex with many event types. The next step shows how to simplify this.

---

## Step 2: Refactor with `Listening` Trait (Optional)

The `Streak\Domain\Event\Listener\Listening` trait helps simplify event handling by routing events to specific methods based on naming conventions.

**1. Use the Trait:**

Modify the `ProjectSummaryProjector` to use the trait:

```php
<?php

namespace App\Application\Projector;

use App\Domain\Project\Event\ProjectCreated;
use App\Domain\Project\Event\ProjectRenamed;
use App\Domain\Project\Event\ProjectArchived;
use Doctrine\DBAL\Connection;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Listener\Listening;

final class ProjectSummaryProjector implements Listener, Listener\Id
{
    use Listening;

    private const TABLE_NAME = 'project_summary';

    // rest of the code

    public function onProjectCreated(ProjectCreated $event): void
    {
        $this->connection->insert(self::TABLE_NAME, [
            'project_id' => (string) $event->projectId,
            'project_name' => $event->initialName,
        ]);
    }

    public function onProjectRenamed(ProjectRenamed $event): void
    {
        $this->connection->update(
            self::TABLE_NAME,
            ['project_name' => $event->newName],
            ['project_id' => (string) $event->projectId]
        );
    }

    public function onProjectArchived(ProjectArchived $event): void
    {
        $this->connection->delete(
            self::TABLE_NAME,
            ['project_id' => (string) $event->projectId]
        );
    }
}
```

*   Added `use Streak\Domain\Event\Listener\Listening;`.
*   **Removed** the manual `on(Event\Envelope $envelope)` method.
*   Added public methods `onProjectCreated`, `onProjectRenamed`, and `onProjectArchived` with specific event type hints and `void` return types.

This makes the event handling logic much cleaner and easier to manage.

**2. Understanding Lifecycle Hooks (Optional):**

The `Listening` trait provides optional private methods that act as hooks around the execution of your specific `on<EventName>` handlers:

*   `private function preEvent(Event $event): void`: Called *before* the specific handler (e.g., `onProjectCreated`) is invoked.
*   `private function postEvent(Event $event): void`: Called *after* the specific handler completes successfully.
*   `private function onException(\Throwable $exception, Event $event): void`: Called if the specific handler throws any `Throwable`.

By default, these methods do nothing. However, you can override them in your listener class to implement cross-cutting concerns. A common use case is managing database transactions, ensuring each event is processed atomically:

```php
    // Example Transactional Hooks (inside your listener class)

    private function preEvent(Event $event): void
    {
        $this->connection->beginTransaction();
    }

    private function postEvent(Event $event): void
    {
        $this->connection->commit();
    }

    private function onException(\Throwable $exception, Event $event): void
    {
        // Attempt to rollback transaction on error
        try {
            $this->connection->rollBack();
        } catch (\Exception $rollbackException) {
            // Log or handle rollback failure if necessary
        }

        // Note: The Listening trait will re-throw the original $exception after calling this hook.
    }
```

Using these hooks keeps your main `on<EventName>` methods focused purely on the business logic of updating the projection state.

This makes the event handling logic much cleaner and easier to manage.

---

## Step 3: Setting the Correct Initial Starting Point with `Picker`

By default, when a Streak `Subscription` starts processing events for a `Listener`, it might begin from the event that initiated the subscription process, or potentially from its last known position if it's resuming. However, for projections like ours that build state from historical data, this is often incorrect. We need to ensure the projector processes *all* relevant events from the very beginning of the event stream to build an accurate `project_summary`.

The `Streak\Domain\Event\Listener\Picker` interface solves this. It allows a listener to specify the exact position in the event stream from which its subscription should start reading. Importantly, this decision is made **only once** when the subscription is initially set up for the listener. This chosen starting point is then permanently associated with that subscription instance.

**1. Implement the Interface:**

Modify the `ProjectSummaryProjector` to implement `Picker` and add the `pick` method.

```php
<?php

namespace App\Application\Projector;

use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Listener\Listening;
use Streak\Domain\Event\Listener\Picker;
use Streak\Domain\EventStore;

final class ProjectSummaryProjector implements Listener, Listener\Id, Picker
{
    use Listening;

    // rest of the code

    public function pick(EventStore $store): Event\Envelope
    {
        // Ensure we always start from the absolute beginning of the event stream
        return $store->stream()->first();
    }
}
```

*   Added `use Streak\Domain\Event\Listener\Picker;` and `use Streak\Domain\EventStore;`.
*   Added `Picker` to the `implements` clause.
*   Implemented the required `pick(EventStore $store)` method, focusing only on this addition.

By implementing `Picker` this way, we ensure that our `ProjectSummaryProjector` will always build its state based on the complete history of relevant project events, regardless of when its subscription is started or restarted.

---

## Step 4: Making it Resettable

Projections often need to be rebuilt (e.g., after code changes or to fix data corruption). The `Resettable` interface allows the Subscription mechanism to clear the projection's state before reprocessing events.

**1. Implement the Interface:**

Add the `Resettable` interface and implement the `reset` method. This example uses a `DROP TABLE`/`CREATE TABLE` approach within a transaction to ensure the schema is correctly re-established.

```php
<?php

namespace App\Application\Projector;

use App\Domain\Project\Event\ProjectCreated;
use App\Domain\Project\Event\ProjectRenamed;
use App\Domain\Project\Event\ProjectArchived;
use Doctrine\DBAL\Connection;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Listener\Listening;
use Streak\Domain\Event\Listener\Picker;
use Streak\Domain\EventStore;
use Streak\Domain\Event\Listener\Resettable;

final class ProjectSummaryProjector implements Listener, Listener\Id, Picker, Resettable
{
    use Listening;

    private const TABLE_NAME = 'project_summary';
    private readonly Listener\Id $id;

    public function __construct(private Connection $connection, ProjectSummaryProjectorId $id) { /* ... */ }
    public function id(): Listener\Id { /* ... */ }
    public function pick(EventStore $store): Event\Envelope { /* ... */ }

    public function reset(): void
    {
        $this->connection->beginTransaction();
        try {
            $this->connection->executeStatement('DROP TABLE IF EXISTS ' . self::TABLE_NAME);
            $createSql = <<<SQL
CREATE TABLE project_summary (
    project_id VARCHAR(36) PRIMARY KEY NOT NULL, -- UUID length
    project_name VARCHAR(255) NOT NULL
);
SQL;
            $this->connection->executeStatement($createSql);
            $this->connection->commit();
        } catch (\Exception $e) {
            // Attempt to rollback transaction on error
            try {
                $this->connection->rollBack();
            } catch (\Exception $rollbackException) {
                // Log or handle rollback failure if necessary
            }
            throw $e; // Re-throw the original exception
        }
    }

    public function onProjectCreated(ProjectCreated $event): void { /* ... */ }
    public function onProjectRenamed(ProjectRenamed $event): void { /* ... */ }
    public function onProjectArchived(ProjectArchived $event): void { /* ... */ }
}
```

*   Added `use Streak\Domain\Event\Listener\Resettable;`.
*   Added `Resettable` to the `implements` clause.
*   Implemented the `reset()` method using `DROP TABLE IF EXISTS` and `CREATE TABLE` within a transaction.

This `reset()` method is called automatically by the subscription mechanism in two situations:
1.  When the subscription processes its very first event after being started initially.
2.  Whenever the `streak:subscription:restart` command is used.
In both cases, it ensures a clean state before event processing begins or resumes. Reprocessing always starts from the event stream position originally determined by the `Picker` interface (implemented in the previous step).

---

## Step 5: Refactor with `Identifying` Trait (Optional)

The logic for managing the `Listener\Id` (implementing the interface, constructor injection, storing the ID) is common boilerplate. Streak provides the `Streak\Domain\Event\Listener\Identifying` trait to simplify this.

**1. Use the Trait:**

Refactor the projector to use the `Identifying` trait, removing the explicit `id` property and method.

```php
<?php

namespace App\Application\Projector;

// Keep use statements for events & Connection from Step 1, 2, 3 & 4
use App\Domain\Project\Event\ProjectCreated;
use App\Domain\Project\Event\ProjectRenamed;
use App\Domain\Project\Event\ProjectArchived;
use Doctrine\DBAL\Connection;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Listener\Listening;
use Streak\Domain\Event\Listener\Picker;
use Streak\Domain\EventStore;
use Streak\Domain\Event\Listener\Resettable;
use Streak\Domain\Event\Listener\Identifying;

// Keep Listener\Id, Picker, Resettable in implements
final class ProjectSummaryProjector implements Listener, Listener\Id, Picker, Resettable
{
    use Listening;
    use Identifying;

    private const TABLE_NAME = 'project_summary';
    // --- REMOVE explicit `private readonly Listener\Id $id;` property declaration ---

    public function __construct(
        private Connection $connection,
        ProjectSummaryProjectorId $id
    ) {
        $this->identifyBy($id);
    }

    // --- REMOVE explicit `id()` method implementation (now provided by the trait) ---

    public function pick(EventStore $store): Event\Envelope { /* ... */ }
    public function reset(): void { /* ... */ }
    public function onProjectCreated(ProjectCreated $event): void { /* ... */ }
    public function onProjectRenamed(ProjectRenamed $event): void { /* ... */ }
    public function onProjectArchived(ProjectArchived $event): void { /* ... */ }
}
```

*   Added `use Streak\Domain\Event\Listener\Identifying;`.
*   Added `use Identifying;` inside the class.
*   **Removed** the explicit `$id` property declaration.
*   Changed the constructor assignment to `$this->identifyBy($id);`.
*   **Removed** the explicit `id()` method implementation.

This refactoring removes the boilerplate code for ID management while keeping the functionality added in previous steps.

---

## Step 6: Create the Listener Factory

To run this projector as a persistent Subscription, the Streak infrastructure needs a way to create instances of it. This is done via a **Factory**.

**1. Create the Factory Class:**

```php
<?php

namespace App\Application\Projector;

use Doctrine\DBAL\Connection;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Listener\Factory as ListenerFactory;

final class ProjectSummaryProjectorFactory implements ListenerFactory
{
    public function __construct(
        private Connection $connection
    ) {
    }

    /**
     * Creates the listener instance for a given ID.
     */
    public function create(Listener\Id $id): Listener
    {
        // Ensure we're creating for the correct ID type
        if (!$id instanceof ProjectSummaryProjectorId) {
            throw new Event\Exception\InvalidListenerIdGiven($id);
        }

        // Instantiate the projector
        return new ProjectSummaryProjector(
            $this->connection,
            $id
        );
    }

    /**
     * Allows the listener to be potentially created based on any event.
     * For a singleton projector, we return the single known instance.
     * This ensures the projector can be initialized ASAP if discovered via events.
     */
    public function createFor(Event\Envelope $event): Listener
    {
         // Return the singleton instance using its known ID
         return $this->create(new ProjectSummaryProjectorId());
    }
}
```

*   Implements `Streak\Domain\Event\Listener\Factory` (aliased as `ListenerFactory`).
*   Injects the `Connection`.
*   Implements `create(Listener\Id $id)`: Creates the projector instance using the provided ID.
*   Implements `createFor(Event\Envelope $event)`: Returns the singleton projector instance, ensuring it can be created/retrieved even if instantiation is triggered by an event.

---

## Step 7: Register the Factory (Symfony)

The factory needs to be registered as a service in Symfony's container and tagged so the StreakBundle can find it.

**1. Configure `services.yaml`:**

```yaml
# config/packages/services.yaml (or similar)
services:
    # ... other services

    # Autoconfigure is usually enabled by default in Symfony
    _defaults:
        autoconfigure: true
        autowire: true

    # Define the service for the factory
    App\Application\Projector\ProjectSummaryProjectorFactory:
        # Arguments are usually autowired (like the DBAL Connection)
        # arguments: ['@doctrine.dbal.default_connection'] # Usually not needed with autowire

        # The 'streak.listener_factory' tag is crucial.
        # If autoconfigure is true, the bundle adds this tag automatically
        # because the class implements Streak\Domain\Event\Listener\Factory.
        # If autoconfigure is false, you MUST add the tag manually:
        # tags: ['streak.listener_factory']
```

*   We define the service for `ProjectSummaryProjectorFactory`.
*   Dependencies (like `Connection`) are typically autowired by Symfony.
*   The key is the `streak.listener_factory` tag. If `autoconfigure: true` is set (the default in modern Symfony), the StreakBundle automatically adds this tag because our factory class implements the `Listener\Factory` interface. If you disable autoconfiguration, you must add the tag manually.

---

## Step 8: Starting the Subscription

With the listener, its ID, and its factory defined and registered, you can now manage and run it as a persistent Subscription using the console commands provided by the StreakBundle.

**1. Use Console Commands:**

*   **Run:** To start processing events from its last known position (or the beginning if new):
    ```bash
    php bin/console streak:subscription:run App\Application\Projector\ProjectSummaryProjectorId a1a5c5a5-4122-48f6-a673-4e176a61f8f8
    ```
    *   `App\Application\Projector\ProjectSummaryProjectorId`: The *type* (class name) of the listener ID.
    *   `a1a5c5a5-4122-48f6-a673-4e176a61f8f8`: The specific *instance ID* (string representation) for this singleton projector.

*   **Restart (Rebuild):** To clear the projection and reprocess all historical events:
    ```bash
    # This command will call the reset() method we implemented
    php bin/console streak:subscription:restart App\Application\Projector\ProjectSummaryProjectorId a1a5c5a5-4122-48f6-a673-4e176a61f8f8
    ```

*   **Other Commands:** Use `pause`, `unpause`, etc., as needed. List all commands with `bin/console list streak`.

You would typically run the `streak:subscription:run` command using a process manager like Supervisor to keep it running in the background on your server, ensuring your projection stays up-to-date.

## Conclusion

You have successfully built a projection (`ProjectSummaryProjector`) that:

*   Listens to specific domain events (`ProjectCreated`, `ProjectRenamed`, `ProjectArchived`).
*   Updates a queryable read model (`project_summary` table).
*   Has a stable identity (`ProjectSummaryProjectorId`).
*   Can be reset and rebuilt (`Resettable`).
*   Can be instantiated by the framework via its `Factory`.
*   Can be run reliably as a persistent Subscription using console commands.

This incremental approach demonstrates how Streak's interfaces and traits allow you to start simple and add necessary features like resettability and standardized identity management as required. You also saw how the `Factory` pattern integrates with the Subscription mechanism for managing listener instances.
