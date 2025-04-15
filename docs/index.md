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

*   **Architecture:** The foundational patterns and building blocks that form the system.
*   **Commands:** Represent an intention to change the state of the system.
*   **Events:** Represent facts about things that have happened in the past.
*   **Event Store:** Responsible for persisting and retrieving streams of events.
*   **Event Bus:** Allows decoupled components to react to published events.
*   **Listeners:** Components that react to events.
*   **Subscriptions:** Managed runtime that provides reliability and persistence.

## Getting Started

1.  **Explore Core Concepts:** Understand the fundamental building blocks of Streak.
    *   [Architecture](./core-concepts/architecture.md)
    *   [Commands](./core-concepts/commands.md)
    *   [Events](./core-concepts/events.md)
    *   [Event Store](./core-concepts/event-store.md)
    *   [Event Bus](./core-concepts/event-bus.md)
    *   [Event Listeners](./core-concepts/listeners.md)
    *   [Subscriptions](./core-concepts/listeners.md#subscriptions)
    *   [Testing](./core-concepts/testing.md)
2.  **Symfony Integration (Optional):** If you are using Symfony, learn how the `StreakBundle` simplifies integration.
    *   [StreakBundle Installation](./symfony-bundle/installation.md)
3.  **Tutorials:** Follow step-by-step guides to build key components of an event-sourced application.
    *   [Building an Aggregate](./tutorials/building-an-aggregate.md) - Create the core domain model with commands and events
    *   [Building a Saga](./tutorials/building-a-saga.md) - Handle cross-aggregate coordination with external systems
    *   [Building a Process Manager](./tutorials/building-a-process-manager.md) - Manage complex workflows with state
    *   [Building a Projection](./tutorials/building-a-projection.md) - Create read models optimized for querying

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
