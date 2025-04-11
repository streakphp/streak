# Symfony Bundle: Service Registration & Autoconfiguration

This document explains how the Streak Symfony Bundle handles the registration and configuration of core Streak services and your application's domain components (Aggregates, Listeners, etc.) within the Symfony Dependency Injection container.

## Core Principles

*   **Convention over Configuration:** The bundle aims to minimize explicit configuration by relying on sensible defaults and detecting your components based on interfaces they implement.
*   **Autowiring & Autoconfiguration:** Leverages Symfony's autowiring and autoconfiguration features to automatically register and wire up services.
*   **Tagging:** Uses Symfony's service tagging mechanism to identify specific types of components (e.g., Listener Factories, Command Handlers) for the bundle's infrastructure.

## Autoconfiguration

By default, if `autoconfigure` is enabled in your `config/services.yaml` (which is the standard Symfony setup), the Streak Bundle automatically detects and configures services that implement key Streak interfaces defined in your `src/` directory.

### Autoconfigured Component Types

1.  **Command Handlers (`Streak\Application\CommandHandler`)**
    *   **Detection:** Services implementing `Streak\Application\CommandHandler`.
    *   **Tagging:** Automatically tagged with `streak.command_handler`.
    *   **Command Bus Registration:** The configured `CommandBus` uses this tag to route commands to the appropriate handler (typically based on method naming conventions like `handle<CommandName>` or type hints).

2.  **Listener Factories (`Streak\Domain\Event\Listener\Factory`)**
    *   **Detection:** Services implementing `Streak\Domain\Event\Listener\Factory`.
    *   **Tagging:** Automatically tagged with `streak.listener_factory`.
    *   **Subscription Management:** The commands (`streak:subscription:*`) use this tag to find factories when creating/managing subscriptions for specific listener classes.
    *   **Event Bus Registration (Potential):** Depending on the `EventBus` implementation configured, listeners associated with tagged factories *might* be automatically registered with the bus. The mechanism could introspect the listener for `on<EventName>` methods or rely on other conventions, but the core `Listener` interface does not define a mechanism for the bus to automatically determine which events it handles.

### Example: Tagging a Projector Factory

If you have a listener factory like this:

```php
<?php

namespace App\Application\Projector;

use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Listener\Factory;
// ... other necessary use statements

class ProjectSummaryProjectorFactory implements Factory
{
    // ... implementation ...
}
```

And autoconfiguration is enabled, you generally **do not** need to manually add the `streak.listener_factory` tag in `config/services.yaml`. The bundle detects the `implements Factory` and adds the tag automatically.

### Explicit Tagging

If autoconfiguration is disabled, or if you need to customize tags (e.g., add attributes), you must define the service explicitly and add the relevant tags:

```yaml
# config/services.yaml
services:
    # Disable autoconfigure for this service if needed
    App\Application\Projector\ProjectSummaryProjectorFactory:
        autoconfigure: false # Optional: only if globally enabled
        arguments: ['@Doctrine\DBAL\Connection']
        tags:
            - { name: 'streak.listener_factory' }

    App\Application\CommandHandler\RegisterUserHandler:
        autoconfigure: false
        arguments: ['@Streak\Domain\EventStore'] # Example dependency
        tags:
            - { name: 'streak.command_handler' }
```

## Default Services

The bundle registers default services for core Streak components based on your configuration in `config/streak.yaml`:

*   `Streak\Domain\EventStore`: Aliased to the configured implementation (`InMemoryEventStore`, `DbalPostgresEventStore`, or custom service).
*   `Streak\Application\CommandBus`: A default synchronous bus or configured integration.
*   `Streak\Domain\EventBus`: A default synchronous bus (often decorated) or configured integration.
*   `Streak\Infrastructure\Domain\EventStore\PublishingEventStore`: Automatically decorates the primary `EventStore` service in most setups.
*   `Streak\Domain\Serializer`: Aliased to the configured serializer service.
*   `Streak\Domain\Event\Subscription\Repository`: A default implementation (e.g., DBAL-based) if configured.
*   Aggregate Repositories/Factories (if provided by the bundle based on configuration).

These default services can usually be injected into your own services using their interface type hints thanks to Symfony's autowiring.

```php
<?php

namespace App\Service;

use Streak\Domain\EventStore;
use Streak\Application\CommandBus;

class MyService
{
    // Autowiring injects the configured EventStore and CommandBus
    public function __construct(
        private EventStore $eventStore,
        private CommandBus $commandBus
    ) {}
}
```

## Disabling Autoconfiguration

While generally recommended, you can disable autoconfiguration for specific services or directories in your `config/services.yaml` if needed, falling back to manual service definition and tagging.
