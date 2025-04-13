# Core Concepts: Queries & Query Bus

In a CQRS (Command Query Responsibility Segregation) approach, **Queries** represent requests for information or data from the system. Unlike [Commands](./commands.md) (which are requests to change state), Queries are inherently read-only and should not cause any side effects.

## Queries

A Query is typically a simple Data Transfer Object (DTO) that encapsulates the parameters needed to retrieve specific information. It represents the question being asked of the system.

Example:
```php
<?php

namespace App\Application\Query;

final class GetProjectList
{
    // Queries can have parameters, e.g., filtering or pagination
    // public function __construct(private ?string $filter = null) {}
}
```

## Query Handlers

A Query Handler is a service responsible for executing a specific type of Query and returning the requested data. Each Query class typically has exactly one corresponding Query Handler.

Query Handlers interact with read models, repositories, or other data sources to fetch the necessary information. They contain the logic to answer the question posed by the Query.

Example:
```php
<?php

namespace App\Application\QueryHandler;

use App\Application\Query\GetProjectList;
use App\Application\DTO\ProjectListItem; // Example DTO for results
use Doctrine\DBAL\Connection; // Or other data source

final class GetProjectListHandler
{
    public function __construct(private Connection $connection)
    {}

    /**
     * @return ProjectListItem[]
     */
    public function __invoke(GetProjectList $query): array
    {
        // Logic to fetch data based on the query
        $qb = $this->connection->createQueryBuilder();
        $rows = $qb->select('id', 'name')
            ->from('project_list_projection') // Example read model table
            ->orderBy('name', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map(
            fn(array $row) => new ProjectListItem($row['id'], $row['name']),
            $rows
        );
    }
}
```
*Note: While the example uses `__invoke`, handlers might follow a convention like `handle<QueryName>(Query $query)`, especially when using helper traits or specific bus implementations.*

## Query Bus

The **Query Bus** is a central dispatcher responsible for routing a Query object to its corresponding Query Handler. It acts as a mediator, decoupling the code that requests data (e.g., a controller or service) from the code that fulfills the request (the Query Handler).

Usage typically looks like this:
```php
<?php

use App\Application\Query\GetProjectList;
use Streak\Application\QueryBus; // Assuming QueryBus interface

class ProjectController
{
    public function __construct(private QueryBus $queryBus)
    {}

    public function listProjects(): Response
    {
        $query = new GetProjectList();
        $projectList = $this->queryBus->dispatch($query); // Dispatch returns the result

        // ... return response with $projectList ...
    }
}
```

The Query Bus implementation (often provided by a framework bundle like the [Symfony Bundle](../symfony-bundle/)) is responsible for finding the correct handler for the dispatched Query and executing it.

## Relationship to Listeners

[Event Listeners](./listeners.md) (specifically [Projectors](./listeners.md#example-projector-with-query-handling)) are responsible for *building* read models based on events. Query Handlers are responsible for *reading* from those models (or other data sources). While it's possible to co-locate simple query handling logic within a Projector class (as shown in the [Listeners documentation](./listeners.md#listeners-as-query-handlers-projections)), separating Query Handlers often leads to a cleaner separation of concerns, especially for more complex queries or read models. 
