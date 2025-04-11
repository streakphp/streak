# Tutorial: Building a Process Manager

This tutorial demonstrates how to build a stateful **Process Manager** using Streak. Process Managers are event listeners that coordinate actions across different parts of your application by reacting to specific domain events, maintaining internal state about the progress of a business process, and potentially dispatching commands based on that state.

This contrasts with stateless Sagas (covered elsewhere), which react to events and dispatch commands without tracking process progress internally.

We'll use a common example: coordinating and tracking the approval process for a document that requires sign-offs from multiple departments (Legal, Finance, and Management).

**Scenario: Document Approval Process**

When a document is submitted, it needs approvals from three different departments (Legal, Finance, and Management). The document is only considered fully approved when all three have signed off.

## Prerequisites: Events & Commands

This tutorial assumes you have the following Domain Events and Commands already defined:

*   **Events:**
    *   `App\Docs\Domain\Event\DocumentSubmitted(documentId: string, authorId: string)`
    *   `App\Docs\Domain\Event\LegalApproved(documentId: string, approverId: string)`
    *   `App\Docs\Domain\Event\FinanceApproved(documentId: string, approverId: string)`
    *   `App\Docs\Domain\Event\ManagerApproved(documentId: string, approverId: string)`
*   **Commands:**
    *   `App\Docs\Application\Command\PublishApprovedDocument(documentId: string)` (Optional, dispatched upon completion)

You will also need the `CommandBus` available if you choose to dispatch a command upon completion.

## Step 1: Basic Process Manager

First, let's create a Process Manager that tracks document approvals:

```php
<?php

declare(strict_types=1);

namespace App\Coordinating\Application\Listener;

use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Infrastructure\Domain\Event\Listener\Listening;

use App\Docs\Domain\Event\DocumentSubmitted;
use App\Docs\Domain\Event\LegalApproved;
use App\Docs\Domain\Event\FinanceApproved;
use App\Docs\Domain\Event\ManagerApproved;

final class DocumentApprovalProcessManager implements 
    Listener,
    Listener\Stateful
{
    use Listening;

    private bool $hasLegalApproval = false;
    private bool $hasFinanceApproval = false;
    private bool $hasManagerApproval = false;

    public function __construct(
        private Id $id
    ) {
    }

    private function onLegalApproved(LegalApproved $event, Event\Envelope $envelope): void
    {
        if ($event->documentId !== $this->id->toString()) {
            return;
        }
        $this->hasLegalApproval = true;
    }

    private function onFinanceApproved(FinanceApproved $event, Event\Envelope $envelope): void
    {
        if ($event->documentId !== $this->id->toString()) {
            return;
        }
        $this->hasFinanceApproval = true;
    }

    private function onManagerApproved(ManagerApproved $event, Event\Envelope $envelope): void
    {
        if ($event->documentId !== $this->id->toString()) {
            return;
        }
        $this->hasManagerApproval = true;
    }

    public function id(): Listener\Id
    {
        return $this->id;
    }

    public function toState(Event\Listener\State $state): Event\Listener\State
    {
        $state = $state->set('legal_approved', $this->hasLegalApproval);
        $state = $state->set('finance_approved', $this->hasFinanceApproval);
        $state = $state->set('manager_approved', $this->hasManagerApproval);
        return $state;
    }

    public function fromState(Event\Listener\State $state): void
    {
        $this->hasLegalApproval = (bool)($state->get('legal_approved') ?? false);
        $this->hasFinanceApproval = (bool)($state->get('finance_approved') ?? false);
        $this->hasManagerApproval = (bool)($state->get('manager_approved') ?? false);
    }
}
```

### Key Points
* Uses `Listening` trait to route events to type-safe `on<EventName>` methods
* Implements `Stateful` to persist approval states between events
* Each method validates that the event belongs to the current document
* Simply tracks the state of approvals without any additional behavior

## Step 2: Adding Completion Tracking

Process Managers often need to stop listening for events once their work is done. For example, once a document has received all required approvals, there's no need to keep tracking further approval events for that document. This is where completion tracking comes in - it tells Streak when a Process Manager instance can be safely deactivated.

By implementing the `Completable` interface, we signal to Streak's subscription system that this Process Manager has a natural end state. When `completed()` returns true, Streak will automatically stop delivering events to this specific Process Manager instance, freeing up resources and preventing unnecessary event processing.

Now let's add completion tracking by implementing `Completable`:

```php
<?php

declare(strict_types=1);

namespace App\Coordinating\Application\Listener;

use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Infrastructure\Domain\Event\Listener\Listening;

final class DocumentApprovalProcessManager implements 
    Listener,
    Listener\Stateful,
    Listener\Completable
{
    use Listening;

    private bool $hasLegalApproval = false;
    private bool $hasFinanceApproval = false;
    private bool $hasManagerApproval = false;

    public function completed(): bool
    {
        return $this->hasLegalApproval 
            && $this->hasFinanceApproval 
            && $this->hasManagerApproval;
    }

    // ... rest of the class remains the same ...
}
```

### Key Points
* Implements `Completable` to signal when all approvals are received
* `completed()` method simply checks if all approvals are present
* When completed, Streak automatically stops delivering events to this Process Manager instance
* No changes to event handlers or state management

## Step 3: Adding Command Dispatching

Now let's add command dispatching when the process is complete:

```php
<?php

declare(strict_types=1);

namespace App\Coordinating\Application\Listener;

use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Infrastructure\Domain\Event\Listener\Listening;
use Streak\Application\CommandBus;
use App\Docs\Domain\Command\PublishApprovedDocument;

final class DocumentApprovalProcessManager implements 
    Listener,
    Listener\Stateful,
    Listener\Completable
{
    use Listening;

    private bool $hasLegalApproval = false;
    private bool $hasFinanceApproval = false;
    private bool $hasManagerApproval = false;

    public function __construct(
        private CommandBus $commandBus,
        private Id $id
    ) {
    }

    private function onLegalApproved(LegalApproved $event, Event\Envelope $envelope): void
    {
        if ($event->documentId === $this->id->toString()) {
            $this->hasLegalApproval = true;
            $this->dispatchCommandIfComplete();
        }
    }

    private function onFinanceApproved(FinanceApproved $event, Event\Envelope $envelope): void
    {
        if ($event->documentId === $this->id->toString()) {
            $this->hasFinanceApproval = true;
            $this->dispatchCommandIfComplete();
        }
    }

    private function onManagerApproved(ManagerApproved $event, Event\Envelope $envelope): void
    {
        if ($event->documentId === $this->id->toString()) {
            $this->hasManagerApproval = true;
            $this->dispatchCommandIfComplete();
        }
    }

    private function dispatchCommandIfComplete(): void
    {
        if ($this->completed()) {
            $this->commandBus->dispatch(
                new PublishApprovedDocument($this->id->toString())
            );
        }
    }

    public function completed(): bool
    {
        return $this->hasLegalApproval 
            && $this->hasFinanceApproval 
            && $this->hasManagerApproval;
    }

    public function toState(Event\Listener\State $state): Event\Listener\State;

    public function fromState(Event\Listener\State $state): void;
}
```

### Key Points
* Adds `CommandBus` for dispatching commands
* Dispatches command when all approvals are received
* Relies on idempotency in command handlers for safety

## Step 3: Process Manager ID

Each Process Manager instance needs a unique ID. Since we're tracking one approval process per document, we'll use the document ID directly:

```php
<?php

declare(strict_types=1);

namespace App\Coordinating\Application\Listener\DocumentApprovalProcessManager;

use Streak\Domain\Event\Listener;

final readonly class Id implements Listener\Id
{
    public function __construct(
        private string $documentId
    ) {
    }

    public function equals(object $id): bool
    {
        return $id instanceof self && $id->documentId === $this->documentId;
    }

    public function toString(): string
    {
        return $this->documentId;
    }

    public static function fromString(string $id): Listener\Id
    {
        return new self($id);
    }

    public function documentId(): string
    {
        return $this->documentId;
    }
}
```

### How Correlation Works

1. When an event arrives (e.g., `LegalApproved` for Document "123"):
   - The correlation logic extracts `documentId: "123"` from the event
   - This becomes the Process Manager ID directly: `"123"`
   - Streak uses this ID to find or create the Process Manager instance

2. All subsequent events for Document "123" will be routed to the same Process Manager instance:
   - `FinanceApproved(documentId: "123")` → Process Manager `"123"`
   - `ManagerApproved(documentId: "123")` → Process Manager `"123"`

3. Meanwhile, events for Document "456" go to a different instance:
   - `LegalApproved(documentId: "456")` → Process Manager `"456"`

## Step 4: Correlation - Managing Multiple Approval Processes

When handling document approvals, we need to track each document's approval process separately. For example, if Document A is waiting for Legal approval while Document B already has Legal but needs Finance approval, we can't mix up their states.

This is where correlation comes in - it helps us maintain a separate Process Manager instance for each document. When an approval event arrives, we use the `documentId` to either:
- Find the existing Process Manager instance for that document, or
- Create a new Process Manager instance if this is the first event for that document

### Implementing Correlation

First, we encapsulate the correlation logic in the Process Manager itself:

```php
<?php

// Inside App\Coordinating\Application\Listener\DocumentApprovalProcessManager

public static function correlate(Event\Envelope $envelope): ProcessManagerId
{
    $event = $envelope->message();
    $documentId = null;

    if ($event instanceof DocumentSubmitted ||
        $event instanceof LegalApproved ||
        $event instanceof FinanceApproved ||
        $event instanceof ManagerApproved
    ) {
        $documentId = $event->documentId;
    }

    if ($documentId === null) {
        throw new InvalidEventGiven(
            'Event ' . $event::class . ' cannot be correlated to a DocumentApprovalProcessManager instance.'
        );
    }

    return new ProcessManagerId($documentId);
}
```

Then the factory uses this correlation to create or find the right Process Manager instance:

```php
final class DocumentApprovalProcessManagerFactory implements Event\Listener\Factory
{
    public function createFor(Event\Envelope $envelope): Event\Listener
    {
        $processManagerId = DocumentApprovalProcessManager::correlate($envelope);
        return $this->create($processManagerId);
    }
}
```

### How Correlation Works

1. When an event arrives (e.g., `LegalApproved` for Document "123"):
   - The correlation logic extracts `documentId: "123"` from the event
   - This becomes the Process Manager ID directly: `"123"`
   - Streak uses this ID to find or create the Process Manager instance

2. All subsequent events for Document "123" will be routed to the same Process Manager instance:
   - `FinanceApproved(documentId: "123")` → Process Manager `"123"`
   - `ManagerApproved(documentId: "123")` → Process Manager `"123"`

3. Meanwhile, events for Document "456" go to a different instance:
   - `LegalApproved(documentId: "456")` → Process Manager `"456"`

This ensures each document's approval process remains isolated and properly tracked.

## Running the Process Manager

Running the `DocumentApprovalProcessManager` as a managed [`Subscription`](../core-concepts/subscriptions.md) is crucial for reliability and state persistence.

The Subscription mechanism automatically handles state loading/saving (via `Stateful`), correlation, event delivery, and completion checking (via `Completable`).

Run the subscription using the [Streak Bundle](../symfony-bundle/console-commands.md) command:

```bash
# Example: Run the subscription for the document approval process manager
php bin/console streak:subscription:run document_approval_pm --setup

# Run continuously
php bin/console streak:subscription:run document_approval_pm
```

## Conclusion

We've built a Process Manager that:
* Routes events to type-safe handler methods
* Tracks multiple approval conditions using persisted state
* Signals completion when all approvals are received
* Uses correlation to maintain separate processes per document
* Runs reliably as a managed Subscription