# Symfony Bundle: Console Commands

The `StreakBundle` provides several console commands to help manage your event store schema, persistent event subscriptions, and aggregate snapshots.

You can list all available commands, including these, by running `bin/console list streak` in your Symfony application's root directory.

## Event Store Schema (DBAL)

These commands are relevant when using the `DbalPostgresEventStore`.

*   **`streak:schema:create`** (`CreateEventStoreSchemaCommand`)
    *   Creates the necessary database table(s) in your configured PostgreSQL database for the DBAL event store.
    *   Useful during initial setup or deployment.
    *   Requires the DBAL connection to be configured correctly.

*   **`streak:schema:drop`** (`DropEventStoreSchemaCommand`)
    *   Drops the event store database table(s).
    *   **Warning:** This is a destructive operation and will delete all persisted events.
    *   Useful in testing or development environments.

## Subscriptions (Persistent Listeners)

Subscriptions are persistent, identifiable [event listeners](../core-concepts/listeners.md) that track their position in the event stream, making them ideal for reliable projections and process managers. See [Core Concepts: Subscriptions](../core-concepts/listeners.md#subscriptions) for details.

These commands manage the lifecycle and execution of these subscriptions:

*   **`streak:subscriptions:run`** (`RunSubscriptionsCommand`)
    *   Runs multiple (or all) registered subscriptions, processing new events from the event store starting from their last known position.
    *   May run continuously or process available events and exit, depending on implementation and options. Requires a configured `Subscription\Repository`.

*   **`streak:subscription:run [subscription-type] [subscription-id]`** (`RunSubscriptionCommand`)
    *   Runs a single, specific subscription identified by its type (class implementing `Listener\Id`) and its string ID.
    *   Retrieves the subscription's state from the `Subscription\Repository` and processes new events via `Subscription::subscribeTo()`.
    *   Options: `--pause-on-error`, `--listening-limit=`.

*   **`streak:subscription:restart [subscription-type] [subscription-id]`** (`RestartSubscriptionCommand`)
    *   Resets the subscription's position in the event stream (usually to the beginning) via the `Subscription\Repository`.
    *   If the underlying listener implements `Listener\Resettable`, its `reset()` method is called to clear its current state or projection data.
    *   This forces the subscription to reprocess historical events upon the next run.
    *   Useful for rebuilding projections or correcting errors.

*   **`streak:subscription:pause [subscription-type] [subscription-id]`** (`PauseSubscriptionCommand`)
    *   Marks the subscription as paused in the `Subscription\Repository`. Paused subscriptions are typically skipped by `streak:subscriptions:run`.

*   **`streak:subscription:unpause [subscription-type] [subscription-id]`** (`UnPauseSubscriptionCommand`)
    *   Marks the subscription as active (not paused) in the `Subscription\Repository`.

## Snapshots

Aggregate Root Snapshotting is a performance optimization where an aggregate's state is periodically saved to avoid replaying its entire event history.

*   **`streak:snapshots:reset`** (`ResetSnapshotsCommand`)
    *   Clears existing snapshots, forcing aggregates to be rebuilt from their full event history the next time they are loaded.
    *   Useful if snapshot logic changes or data becomes corrupted. Requires a configured snapshot store.

## Usage

Run these commands using the Symfony console:

```bash
# Example: Create the event store schema
bin/console streak:schema:create

# Example: Run a specific subscription (assuming App\Domain\Listeners\MyListenerId)
bin/console streak:subscription:run App\Domain\Listeners\MyListenerId my-listener-instance-1

# Example: Restart all projections of a certain type
# (May require specific command implementation or looping logic)
# bin/console streak:subscription:restart App\ReadModel\ProjectProjection ...

# Example: Reset snapshots
bin/console streak:snapshots:reset
```

Consult the help for each command (`bin/console help <command_name>`) for specific arguments and options. 
