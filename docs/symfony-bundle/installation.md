# Symfony Bundle: Installation & Setup

The `streak/streak-bundle` provides integration between the Streak framework and the Symfony framework, simplifying configuration and wiring components together using Symfony's Dependency Injection container and conventions.

## Requirements

*   PHP (Check `composer.json` in `streak/streak-bundle` for specific version constraints)
*   Symfony Framework (Check `composer.json` for compatible versions)
*   Streak Core (`streak/streak`)

## Installation

1.  **Require the Bundle:**
    Use Composer to add the bundle to your Symfony project:

    ```bash
    composer require streak/streak-bundle
    ```

    Composer will also install `streak/streak` as a dependency if it's not already present.

2.  **Enable the Bundle:**
    In most modern Symfony applications (using Flex), the bundle should be automatically enabled by adding its recipe. Check your `config/bundles.php` file and ensure the following line exists and is uncommented:

    ```php
    <?php
    // config/bundles.php

    return [
        // ... other bundles
        Streak\StreakBundle\StreakBundle::class => ['all' => true],
    ];
    ```

    If you are not using Symfony Flex, you might need to add this line manually.

## Basic Configuration

The bundle provides sensible defaults, but you'll likely need to configure the Event Store persistence.

Configuration is typically done in `config/packages/streak.yaml` (or `.php`).

**Example: Configuring InMemory Event Store (for testing/dev):**

```yaml
# config/packages/streak.yaml
streak:
    event_store: memory # Use the in-memory implementation
```

**Example: Configuring DBAL Postgres Event Store:**

```yaml
# config/packages/streak.yaml
streak:
    event_store:
        dbal:
            # The service ID of your Doctrine DBAL connection
            # Typically 'doctrine.dbal.default_connection' if using DoctrineBundle
            connection: doctrine.dbal.default_connection
            # Optional: Specify a custom table name (defaults usually exist)
            # table: 'event_store'
```

**Key Configuration Areas:**

*   `event_store`: Defines which event store implementation to use (`memory`, `dbal`, or potentially a custom service ID).
    *   `dbal`: Requires specifying the Doctrine DBAL connection service and optionally the table name.
*   `serializer`: Configures the serializer used by persistence mechanisms (like the DBAL store) to convert event objects to/from a storable format. Often integrates with Symfony's Serializer component.
*   `command_bus` / `event_bus`: Configuration related to bus implementations (e.g., if using Symfony Messenger integration).

Refer to the `Configuration Reference` section (or potentially explore the bundle's `Configuration.php` file) for all available options.

## Service Auto-Configuration

The bundle automatically configures services and registers handlers/listeners based on interfaces:

*   **Command Handlers:** Services implementing `Streak\Domain\CommandHandler` (or specific command interfaces if configured) are automatically tagged and registered with the Command Bus.
*   **Event Listeners:** Services implementing `Streak\Domain\Event\Listener` are automatically tagged and registered with the Event Bus.
*   **Aggregate Repositories:** The bundle often provides default repository implementations that use the configured Event Store.

This auto-configuration significantly reduces the amount of manual service definition required.

## Next Steps

*   Explore the detailed [Configuration Reference](./configuration.md).
*   Understand how [Service Registration](./service-registration.md) works.
*   Set up persistence, e.g., using the [DBAL Event Store](./../tutorials/setting-up-persistence.md). 
