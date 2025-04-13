# Tutorial: Setting Up Persistence (DBAL Event Store)

This tutorial explains how to configure Streak to persist events using a PostgreSQL database via the `DbalPostgresEventStore` and the `StreakBundle` in a Symfony application.

**Goal:** Configure the application to use a persistent event store instead of the default in-memory one.

**Prerequisites:**

*   A Symfony application set up.
*   `streak/streak-bundle` installed and enabled (see [Installation Guide](../symfony-bundle/installation.md)).
*   A PostgreSQL database server running and accessible.
*   Doctrine DBAL configured in your Symfony application (usually via `doctrine/doctrine-bundle`).

## 1. Configure Doctrine DBAL Connection

Ensure you have a DBAL connection configured in your Symfony application, typically in `config/packages/doctrine.yaml`. The important part is having a connection service available (e.g., `doctrine.dbal.default_connection`).

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

    # If using ORM (not strictly required for Streak DBAL EventStore but common):
    # orm:
    #     auto_generate_proxy_classes: true
    #     naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
    #     auto_mapping: true
    #     mappings:
    #         App:
    #             is_bundle: false
    #             dir: '%kernel.project_dir%/src/Entity' # Example ORM mapping
    #             prefix: 'App\Entity'
    #             alias: App
```

Make sure your `DATABASE_URL` environment variable (in `.env` or `.env.local`) points to your PostgreSQL database.

## 2. Configure StreakBundle

Modify your `config/packages/streak.yaml` file to tell the bundle to use the DBAL event store and specify which DBAL connection to use.

```yaml
# config/packages/streak.yaml
streak:
    event_store:
        dbal:
            # Service ID of the DBAL connection configured in doctrine.yaml
            connection: doctrine.dbal.default_connection
            # table: 'event_store' # Optional: Defaults to 'event_store'

    # You also NEED to configure a serializer for the DBAL store
    serializer:
        # Assuming you use Symfony's serializer component
        service: serializer
```

*   `event_store.dbal.connection`: Points to the service ID of your configured DBAL connection.
*   `event_store.dbal.table`: (Optional) You can change the table name if needed, but the default `event_store` is usually fine.
*   `serializer.service`: **Crucial** for the DBAL store. It needs a serializer service (like Symfony's default `serializer`) to convert your event objects into a storable format (usually JSON) and back. Ensure your Symfony serializer is configured with necessary normalizers (e.g., `ObjectNormalizer`, `DateTimeNormalizer`, `UidNormalizer` if using UUIDs as IDs) in `config/packages/framework.yaml` or similar.

## 3. Create the Database Schema

The `DbalPostgresEventStore` requires a specific table structure. Use the console command provided by the `StreakBundle` to create it:

```bash
php bin/console streak:schema:create
```

This command will connect to the database specified in your DBAL configuration and execute the necessary `CREATE TABLE` statement.

If you need to remove the table later (e.g., in tests or dev), you can use:

```bash
# Warning: Deletes all events!
php bin/console streak:schema:drop
```

## 4. Verify Configuration

At this point, Streak is configured to use the persistent DBAL store.

*   Your aggregates, when loaded and saved via repositories provided by or configured through the bundle, will now read from and write to the PostgreSQL database.
*   The `PublishingEventStore` decorator (applied automatically by the bundle) will ensure events are published to the Event Bus only after being successfully saved to the database.

## Next Steps

*   Ensure your Symfony Serializer configuration (`framework.yaml`) includes normalizers capable of handling your Event objects and their properties (especially DateTimes, UUIDs/IDs, and nested objects).
*   Implement repositories for your Aggregate Roots that use the injected `Streak\Domain\EventStore` to load and save aggregates.
*   Follow the [Handling Commands & Events](./handling-commands-events.md) tutorial to see how to interact with aggregates persisted this way. 
