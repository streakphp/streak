# Building a Saga

This tutorial demonstrates how to build a stateless Saga that integrates your system with Salesforce. The Saga will listen for subscription and order events, then dispatch commands to sync this data to Salesforce.

**Goal:** Create a Saga that listens for domain events and dispatches commands to sync data with an external system (Salesforce).

## Step 1: Creating the Saga ID

Since this is a singleton Saga, we'll use a simple ID:

```php
<?php

declare(strict_types=1);

namespace App\Integration\Application\Listener\SalesforceSaga;

use Streak\Domain\Event\Listener;

final readonly class Id implements Listener\Id
{
    private function __construct()
    {
    }

    public function equals(object $id): bool
    {
        return $id instanceof self;
    }

    public function toString(): string
    {
        return 'salesforce_sync';
    }

    public static function fromString(string $id): Listener\Id
    {
        if ($id !== 'salesforce_sync') {
            throw new \InvalidArgumentException('Invalid ID for SalesforceSaga');
        }
        return new self();
    }

    public static function create(): self
    {
        return new self();
    }
}
```

## Step 2: Creating the Saga

```php
<?php

declare(strict_types=1);

namespace App\Integration\Application\Listener;

use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Infrastructure\Domain\Event\Listener\Listening;
use Streak\Infrastructure\Domain\Event\Listener\Identifiable;
use Streak\Application\CommandBus;
use App\Subscription\Domain\Event\PaidSubscriptionStarted;
use App\Order\Domain\Event\OrderCompleted;
use App\Integration\Domain\Command\SyncCustomerToSalesforce;
use App\Integration\Domain\Command\SyncOpportunityToSalesforce;
use App\Integration\Application\Listener\SalesforceSaga\Id;

final class SalesforceSaga implements Listener
{
    use Listening;
    use Identifiable;

    public function __construct(
        private CommandBus $commandBus,
        private Id $id
    ) {
    }

    private function onPaidSubscriptionStarted(PaidSubscriptionStarted $event): void
    {
        $this->commandBus->dispatch(
            new SyncCustomerToSalesforce(
                $event->userId,
                $event->email,
                $event->name,
                $event->planType,
                $event->startedAt
            )
        );
    }

    private function onOrderCompleted(OrderCompleted $event): void
    {
        $this->commandBus->dispatch(
            new SyncOpportunityToSalesforce(
                $event->orderId,
                $event->userId,
                $event->amount,
                $event->products,
                $event->completedAt
            )
        );
    }
}
```

### Key Points
* Uses `Identifiable` trait to implement ID handling
* Uses `Listening` trait to handle events via `on<EventName>` methods
* Stateless - no internal state to track
* Pure event-to-command transformation
* Each event triggers exactly one command

## Step 3: Saga Factory

Since this is a singleton Saga (we only need one instance), the factory is simple:

```php
<?php

declare(strict_types=1);

namespace App\Integration\Application\Listener;

use Streak\Domain\Event;
use Streak\Application\CommandBus;
use App\Integration\Application\Listener\SalesforceSaga\Id;

final class SalesforceSagaFactory implements Event\Listener\Factory
{
    public function __construct(
        private CommandBus $commandBus
    ) {
    }

    public function createFor(Event\Envelope $envelope): Event\Listener
    {
        return $this->create(Id::create());
    }

    public function create(Event\Listener\Id $id): Event\Listener
    {
        if (!$id instanceof Id) {
            throw new \InvalidArgumentException('Invalid ID type given for SalesforceSaga');
        }
        return new SalesforceSaga($this->commandBus, $id);
    }
}
```

### Key Points
* Always returns the same Saga type with the same ID
* No correlation needed - all events go to the same instance
* Injects required dependencies

## Running the Saga

The Saga is reactive and will automatically start when it receives its first event. There's no need to manually start it - Streak will handle the lifecycle automatically.

### Key Points
* Starts automatically when receiving relevant events
* Runs reliably as a managed Subscription
* Processes events in order
* Automatically handles retries on failures

## What's Next?
* Add more event handlers for other Salesforce entities
* Implement the command handlers that do the actual Salesforce API calls
* Add error handling and logging
* Consider adding idempotency checks in command handlers 
