# Symfony Bundle: Configuration Reference

This document outlines the main configuration options available for the `StreakBundle` in your Symfony application. Configuration is typically managed in `config/packages/streak.yaml`.

```yaml
# config/packages/streak.yaml (Example Structure)
streak:
    # Event Store Configuration (Required)
    event_store:
        # Choose ONE implementation:
        memory: ~  # Use InMemoryEventStore (primarily for testing)
        dbal:      # Use DbalPostgresEventStore
            connection: doctrine.dbal.default_connection # Service ID of DBAL connection (Required)
            # table: 'event_store' # Optional: Custom table name
        # service: my_custom_event_store_service_id # Optional: Use a custom service ID

    # Serializer Configuration (Often required for persistent stores like DBAL)
    serializer:
        # Example: Using Symfony's Serializer component
        service: serializer # Service ID of the PSR-compliant serializer
        # You might need specific normalizers configured for your events
        # depending on your Symfony serializer setup.

    # Aggregate Root Configuration (Optional)
    # aggregate_roots:
        # Define specific repository services or configurations per aggregate type if needed
        # Example (Conceptual - Actual structure may vary):
        # App\Domain\MyAggregate:
        #     repository: App\Infrastructure\MyAggregateRepository
        #     factory: App\Infrastructure\MyAggregateFactory

    # Command Bus Configuration (Optional - Often integrates with framework features)
    # command_bus:
        # service: my_custom_command_bus # Use a custom command bus service
        # messenger: # Integrate with Symfony Messenger (Conceptual)
        #     bus_name: command.bus

    # Event Bus Configuration (Optional - Often integrates with framework features)
    # event_bus:
        # service: my_custom_event_bus # Use a custom event bus service
        # messenger: # Integrate with Symfony Messenger (Conceptual)
        #     bus_name: event.bus
```

## Top-Level Keys

*   `streak`: The main configuration key for the bundle.

## `event_store` (Required)

Configures the primary `Streak\Domain\EventStore` implementation used by the application.

*   **`memory:`**
    *   Uses `Streak\Infrastructure\Domain\EventStore\InMemoryEventStore`.
    *   No further configuration needed under this key.
    *   Suitable for testing or development where persistence is not required.
*   **`dbal:`**
    *   Uses `Streak\Infrastructure\Domain\EventStore\DbalPostgresEventStore`.
    *   Requires a `connection` key specifying the service ID of a Doctrine DBAL connection configured in your application (e.g., `doctrine.dbal.default_connection`).
    *   Optionally accepts a `table` key to specify the database table name (a default is usually provided).
    *   Requires a properly configured `serializer`.
*   **`service:`**
    *   Allows specifying the service ID of a custom `EventStore` implementation if you are not using the built-in ones.

**Important:** In most production scenarios, the configured event store service (e.g., the DBAL one) is automatically decorated by the `PublishingEventStore` to ensure events are published to the `EventBus` *after* being persisted.

## `serializer` (Conditional)

Configures the serialization mechanism used by persistent event stores.

*   **`service:`**
    *   Specifies the service ID of a PSR-compliant Serializer (like Symfony's `serializer` service).
    *   Ensure your serializer is configured with appropriate normalizers (e.g., `ObjectNormalizer`, `DateTimeNormalizer`, `UidNormalizer` if using Symfony UID) to handle your specific Event classes and their properties.

## `aggregate_roots` (Optional)

Allows for potential customization of how aggregate roots are handled, such as defining specific repository or factory services.

*   *(Details depend on the specific features implemented in the bundle. Check the bundle's `DependencyInjection/Configuration.php` for the exact structure if needed.)*

## `command_bus` (Optional)

Configures the `Streak\Application\CommandBus`.

*   **`service:`**
    *   Specify a custom service ID for the command bus.
*   **`messenger:`** *(Conceptual)*
    *   If the bundle integrates with Symfony Messenger, this section might configure which Messenger bus to use for commands.

If not configured, the bundle likely provides a default synchronous command bus implementation.

## `event_bus` (Optional)

Configures the `Streak\Domain\EventBus`.

*   **`service:`**
    *   Specify a custom service ID for the event bus.
*   **`messenger:`** *(Conceptual)*
    *   If the bundle integrates with Symfony Messenger, this section might configure which Messenger bus to use for events.

If not configured, the bundle likely provides a default synchronous event bus implementation that integrates with the `PublishingEventStore`. 
