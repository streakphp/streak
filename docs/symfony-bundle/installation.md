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

    # Required for DBAL store: Configure serializer
    serializer:
        service: serializer # Service ID of your PSR-compliant serializer
```

### DBAL Connection Setup

When using the DBAL event store, you need to configure a DBAL connection in your Symfony application:

```yaml
# config/packages/doctrine.yaml
doctrine:
    dbal:
        # configure these for your database server
        url: '%env(resolve:DATABASE_URL)%' # Recommended way using env var
        # Or configure explicitly:
        # driver: 'pdo_pgsql'
        # host: 'localhost'
        # port: 5432
        # dbname: 'my_app_db'
        # user: 'db_user'
        # password: 'db_password'
        # charset: UTF8
        # server_version: '15' # Optional: Specify server version
```

Make sure your `DATABASE_URL` environment variable (in `.env` or `.env.local`) points to your PostgreSQL database.

### Schema Management

The `DbalPostgresEventStore` requires a specific table structure. Use the console commands provided by the `StreakBundle` to manage it:

```bash
# Create the event store table
php bin/console streak:schema:create

# Remove the table (Warning: Deletes all events!)
php bin/console streak:schema:drop
```

### Verifying Configuration

After setting up the DBAL event store:

1. Your aggregates will read from and write to the PostgreSQL database
2. The `PublishingEventStore` decorator (applied automatically) ensures events are published to the Event Bus only after being successfully saved
3. Ensure your Symfony Serializer configuration includes necessary normalizers for your Event objects (especially for DateTimes, UUIDs/IDs, and nested objects)

## Service Auto-Configuration

The bundle automatically configures services and registers handlers/listeners based on interfaces:

*   **Command Handlers:** Services implementing `Streak\Domain\CommandHandler` (or specific command interfaces if configured) are automatically tagged and registered with the Command Bus.
*   **Event Listeners:** Services implementing `Streak\Domain\Event\Listener` are automatically tagged and registered with the Event Bus.
*   **Aggregate Repositories:** The bundle often provides default repository implementations that use the configured Event Store.

This auto-configuration significantly reduces the amount of manual service definition required.

## Next Steps

*   Explore the detailed [Configuration Reference](./configuration.md).
*   Understand how [Service Registration](./service-registration.md) works.
*   Follow the [Building an Aggregate](../tutorials/building-an-aggregate.md) tutorial to see how to implement commands and events in your application. 
