# Streak PHP Event Sourcing Framework

Welcome to the documentation for Streak, a PHP framework designed for building applications using the Event Sourcing pattern.

## Philosophy

Streak aims to provide a flexible and robust foundation for event-sourced applications by focusing on:

*   **Domain-Driven Design (DDD):** Encouraging the use of core DDD concepts like Aggregates, Entities, Value Objects, and Domain Events.
*   **Simplicity:** Offering clear interfaces and components that are easy to understand and use.
*   **Extensibility:** Allowing developers to swap out implementations (e.g., Event Store persistence) to fit their specific needs.
*   **Testability:** Making it easy to unit test domain logic and application services.

## Core Concepts

Streak is built around several key concepts central to Event Sourcing and DDD:

*   **Aggregates:** Encapsulate state and business logic, ensuring consistency boundaries. Changes to aggregates are recorded as a sequence of events.
*   **Events:** Represent facts about things that have happened in the past. They are the source of truth for the state of aggregates.
*   **Event Store:** Responsible for persisting and retrieving streams of events for aggregates.
*   **Commands:** Represent an intention to change the state of the system. They are handled by aggregates or command handlers.
*   **Event Bus:** Allows decoupled components to react to published events.
*   **Listeners:** Components that react to events. Can be stateful (maintaining state between events) or stateless (pure event-to-command translation).
*   **Subscriptions:** Managed runtime that provides reliability and persistence for event listeners.

## Getting Started

1.  **Explore Core Concepts:** Understand the fundamental building blocks of Streak.
    *   [Aggregates & Event Sourcing](./core-concepts/aggregates.md)
    *   [Events](./core-concepts/events.md)
    *   [Event Store](./core-concepts/event-store.md)
    *   [Commands](./core-concepts/commands.md)
    *   [Event Bus](./core-concepts/event-bus.md)
    *   [Event Listeners](./core-concepts/listeners.md)
    *   [Subscriptions](./core-concepts/subscriptions.md)
2.  **Symfony Integration (Optional):** If you are using Symfony, learn how the `StreakBundle` simplifies integration.
    *   [StreakBundle Installation](./symfony-bundle/installation.md)
3.  **Tutorials:** Follow step-by-step guides to build key parts of an event-sourced application.
    *   [Building an Aggregate](./tutorials/building-an-aggregate.md)
    *   [Setting up Persistence](./tutorials/setting-up-persistence.md)
    *   [Handling Commands & Events](./tutorials/handling-commands-events.md)
    *   [Building a Saga](./tutorials/building-a-saga.md)
    *   [Building a Process Manager](./tutorials/building-a-process-manager.md)
    *   [Building a Projection](./tutorials/building-a-projection.md)

## Installation

Installation typically involves using Composer.

```bash
composer require streak/streak
```

For integration with Symfony, you will also need the bundle:

```bash
composer require streak/streak-bundle
```

Refer to the specific installation guides for more details. 
