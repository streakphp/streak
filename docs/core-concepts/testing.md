# Testing in Streak

Streak provides a robust testing infrastructure that makes it easy to test your event-sourced applications. The testing approach is based on the Given-When-Then pattern, which is particularly well-suited for event-sourced systems.

## Core Testing Components

### Testing Infrastructure

The testing infrastructure provides several helpful components:

1. **In-Memory Event Store**
   - Used for testing without a real database
   - Maintains events in memory during tests
   - Supports all Event Store operations

2. **Null Event Bus**
   - No-op implementation for testing
   - Useful when event publishing is not the focus of the test

3. **Test Doubles**
   - Snapshotter implementations for testing
   - Command bus implementations for testing
   - Repository implementations for testing

### Test Scenarios

Streak provides several scenario builders for different testing contexts:

1. **Aggregate Root Testing** (`AggregateRoot\TestCase`)
   ```php
   class MyAggregateTest extends AggregateRoot\TestCase
   {
       public function testCommand(): void
       {
           $this->for($id)
               ->given($event1, $event2)
               ->when($command)
               ->then($expectedEvent1, $expectedEvent2);
       }
   }
   ```

2. **Event Listener Testing** (`Listener\TestCase`)
   ```php
   class MyListenerTest extends Listener\TestCase
   {
       public function testEventHandling(): void
       {
           $this->given($event1, $event2)
               ->when($newEvent)
               ->then($expectedCommand)
               ->assert();
       }
   }
   ```

## Testing Patterns

### 1. Event Sourcing Tests

When testing event-sourced aggregates:
- Use `given()` to set up the aggregate's history
- Use `when()` to execute a command
- Use `then()` to verify the produced events

```php
public function testDeactivateProject(): void
{
    /** @var ProjectId $projectId */
    $projectId = new ProjectId('project-123');
    
    $this->for($projectId)
        ->given(
            new ProjectCreated($projectId, 'Project Name'),
            new ProjectStarted($projectId)
        )
        ->when(new DeactivateProject($projectId))
        ->then(new ProjectDeactivated($projectId));
}
```

### 2. Event Listener Tests

When testing event listeners:
- Use `given()` to set up the listener's state
- Use `when()` to send a new event
- Use `then()` to verify commands or state changes

```php
public function testProjectionUpdated(): void
{
    /** @var ProjectId $projectId */
    $projectId = new ProjectId('project-123');
    
    $this->given(new ProjectCreated($projectId, 'Project Name'))
        ->when(new ProjectRenamed($projectId, 'New Name'))
        ->then(new UpdateProjectionCommand($projectId, 'New Name'))
        ->assert();
}
```

## Best Practices

1. **Test Organization**
   - Group tests by aggregate/listener
   - Use descriptive test method names
   - Follow the Given-When-Then pattern consistently

2. **Test Data**
   - Use meaningful test data
   - Create helper methods for common event patterns
   - Use constants for fixed values

3. **Assertions**
   - Verify event content, not just event types
   - Check for correct command handling
   - Validate state changes when relevant

4. **Error Cases**
   - Test error conditions explicitly
   - Verify error handling behavior
   - Test concurrent write scenarios

## Example: Complete Test Case

```php
class ProjectAggregateTest extends AggregateRoot\TestCase
{
    private ProjectId $projectId;
    private Clock $clock;
    private ProjectService $projectService;

    protected function setUp(): void
    {
        $this->projectId = new ProjectId('project-1');
        $this->clock = new SystemClock();
        $this->projectService = new ProjectService();
    }

    /**
     * Required: Create and return the factory for your aggregate
     */
    protected function createFactory(): Domain\AggregateRoot\Factory
    {
        return new ProjectAggregateFactory(
            $this->clock,
            $this->projectService
        );
    }

    public function testProjectCreation(): void
    {
        $this->for($this->projectId)
            ->given()  // No previous events
            ->when(new CreateProject($this->projectId, 'New Project'))
            ->then(new ProjectCreated($this->projectId, 'New Project'));
    }

    public function testProjectRename(): void
    {
        $this->for($this->projectId)
            ->given(new ProjectCreated($this->projectId, 'Old Name'))
            ->when(new RenameProject($this->projectId, 'New Name'))
            ->then(new ProjectRenamed($this->projectId, 'New Name'));
    }

    public function testCannotRenameDeactivatedProject(): void
    {
        $this->for($this->projectId)
            ->given(
                new ProjectCreated($this->projectId, 'Project'),
                new ProjectDeactivated($this->projectId)
            )
            ->when(new RenameProject($this->projectId, 'New Name'))
            ->then(/* no events expected */);
    }
}
```

## Optional Test Case Methods

You can override these optional methods to customize testing behavior:

### For Aggregate Root Tests

```php
// Optional: Override if you need a custom command handler
protected function createHandler(
    Domain\AggregateRoot\Factory $factory,
    Domain\AggregateRoot\Repository $repository
): Domain\CommandHandler {
    return new MyCustomHandler($repository);
    // Default implementation uses AggregateRootHandler
}

// Optional: Override for custom snapshot serialization
protected function createSnapshotterSerializer(): Serializer
{
    return new MyCustomSerializer();
    // Default uses PhpSerializer
}

// Optional: Override for custom snapshot storage
protected function createSnapshotterStorage(): Snapshotter\Storage
{
    return new MyCustomStorage();
    // Default uses InMemoryStorage
}
```

### For Event Listener Tests

```php
// Optional: Override for custom command handling
protected function createCommandBus(): CommandBus
{
    return new MyCustomCommandBus();
    // Default uses NullCommandBus
}
``` 
