# Core Concepts: Queries & Query Bus

In a CQRS (Command Query Responsibility Segregation) approach, **Queries** represent requests for information or data from the system. Unlike [Commands](./commands.md) (which are requests to change state), Queries are inherently read-only and should not cause any side effects.

## Queries

A Query is a marker interface that represents a request for information. Streak defines this interface in the `Streak\Domain` namespace:

```php
<?php

namespace Streak\Domain;

interface Query
{
}
```

When implementing your own queries, you create DTOs that implement this interface:

```php
<?php

namespace App\Domain\Query;

use Streak\Domain\Query;

final class GetProjectList implements Query
{
    public function __construct(
        public readonly ?string $filter = null
    ) {
    }
}
```

## Query Handlers

A Query Handler is responsible for executing a specific type of Query and returning the requested data. Streak defines the `QueryHandler` interface:

```php
<?php

namespace Streak\Domain;

interface QueryHandler
{
    /**
     * @throws Exception\QueryNotSupported
     *
     * @return mixed
     */
    public function handleQuery(Query $query);
}
```

When implementing a handler, you can either manually handle the query routing or use the provided trait.

### Raw Query Handler Implementation

```php
<?php

namespace App\Domain\QueryHandler;

use App\Domain\Query\GetProjectList;
use App\Domain\DTO\ProjectListItem;
use Streak\Domain\Query;
use Streak\Domain\QueryHandler;
use Streak\Domain\Exception\QueryNotSupported;
use Doctrine\DBAL\Connection;

final class ProjectQueryHandler implements QueryHandler
{
    public function __construct(private Connection $connection)
    {
    }

    public function handleQuery(Query $query)
    {
        if (!$query instanceof GetProjectList) {
            throw new QueryNotSupported($query);
        }
        
        /** @var GetProjectList $query */
        $qb = $this->connection->createQueryBuilder();
        $qb->select('id', 'name')
            ->from('project_list_projection')
            ->orderBy('name', 'ASC');
            
        if ($query->filter !== null) {
            $qb->where('name LIKE :filter')
               ->setParameter('filter', '%' . $query->filter . '%');
        }
        
        $rows = $qb->executeQuery()->fetchAllAssociative();

        return array_map(
            fn(array $row) => new ProjectListItem($row['id'], $row['name']),
            $rows
        );
    }
}
```

### Using the Handling Trait

The `Handling` trait simplifies query handling by automatically routing queries to the appropriate handler methods:

```php
<?php

namespace App\Domain\QueryHandler;

use App\Domain\Query\GetProjectList;
use App\Domain\DTO\ProjectListItem;
use Streak\Domain\Query;
use Streak\Domain\QueryHandler;
use Streak\Domain\Query\Handling;
use Doctrine\DBAL\Connection;

final class ProjectQueryHandler implements QueryHandler
{
    use Handling;

    public function __construct(private Connection $connection)
    {
    }

    /**
     * @return ProjectListItem[]
     */
    public function handleGetProjectList(GetProjectList $query): array
    {
        /** @var Connection $connection */
        $connection = $this->connection;
        $qb = $connection->createQueryBuilder();
        $qb->select('id', 'name')
            ->from('project_list_projection')
            ->orderBy('name', 'ASC');
            
        if ($query->filter !== null) {
            $qb->where('name LIKE :filter')
               ->setParameter('filter', '%' . $query->filter . '%');
        }
        
        $rows = $qb->executeQuery()->fetchAllAssociative();

        return array_map(
            fn(array $row) => new ProjectListItem($row['id'], $row['name']),
            $rows
        );
    }
}
```

The trait automatically:
- Routes queries to type-specific handler methods
- Validates query types
- Throws appropriate exceptions for unsupported queries

## Handling Multiple Queries in a Single Handler

One of the biggest advantages of using the `Handling` trait is the ability to handle multiple query types in a single handler class:

```php
<?php

namespace App\Domain\QueryHandler;

use App\Domain\Query\GetProjectList;
use App\Domain\Query\GetProjectDetails;
use App\Domain\Query\GetProjectTasks;
use App\Domain\DTO\ProjectListItem;
use App\Domain\DTO\ProjectDetails;
use App\Domain\DTO\TaskItem;
use Streak\Domain\QueryHandler;
use Streak\Domain\Query\Handling;
use Doctrine\DBAL\Connection;

final class ProjectQueryHandler implements QueryHandler
{
    use Handling;

    public function __construct(private Connection $connection)
    {
    }

    /**
     * @return ProjectListItem[]
     */
    public function handleGetProjectList(GetProjectList $query): array { /* ... */ }
    
    /**
     * @return ProjectDetails|null
     */
    public function handleGetProjectDetails(GetProjectDetails $query): ?ProjectDetails { /* ... */ }
    
    /**
     * @return TaskItem[]
     */
    public function handleGetProjectTasks(GetProjectTasks $query): array { /* ... */ }
}
```

This approach allows you to:
- Group related query handlers in a single class
- Maintain strong typing for each query type
- Automatically route queries to the correct handler method
- Avoid repetitive type checking and dispatch logic

## Query Bus

The **Query Bus** is a central dispatcher responsible for routing a Query object to its corresponding Query Handler. Streak defines the `QueryBus` interface:

```php
<?php

namespace Streak\Application;

use Streak\Domain\Exception\CommandNotSupported;
use Streak\Domain\Query;

interface QueryBus
{
    /**
     * @throws CommandNotSupported
     *
     * @return mixed
     */
    public function dispatch(Query $query);
}
```

Usage typically looks like this:

```php
<?php

namespace App\Infrastructure\Controller;

use App\Domain\Query\GetProjectList;
use Streak\Application\QueryBus;
use Symfony\Component\HttpFoundation\Response;

final class ProjectController
{
    public function __construct(private QueryBus $queryBus)
    {
    }

    public function listProjects(string $filter = null): Response
    {
        /** @var GetProjectList $query */
        $query = new GetProjectList($filter);
        
        /** @var array $projectList */
        $projectList = $this->queryBus->dispatch($query);

        return $this->render('projects/list.html.twig', [
            'projects' => $projectList,
            'filter' => $query->filter
        ]);
    }
}
```

The Query Bus implementation (often provided by a framework bundle like the [Symfony Bundle](../symfony-bundle/)) is responsible for finding the correct handler for the dispatched Query and executing it.

## Relationship to Listeners

[Event Listeners](./listeners.md) (specifically [Projectors](../tutorials/building-a-projection.md)) are responsible for *building* read models based on events. Query Handlers are responsible for *reading* from those read models.
