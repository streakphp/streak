# Documentation Style Guide

This guide outlines the principles and standards for Streak documentation.

## Document Types

### Core Concepts
- Explain what a feature is and why it exists
- Focus on architectural decisions and trade-offs
- Show minimal, complete code examples
- Link to tutorials for implementation details

### Tutorials
- Show how to implement features step by step
- Start with minimal working examples
- Add complexity gradually with clear reasoning
- Reference core concepts for deeper understanding
- Present as standalone guides, not as part of a series
- Each tutorial should be self-contained and complete

#### Tutorial Progression
- Begin with the simplest working implementation
- Add one concept at a time
- Ensure each step is complete and functional
- Explain the problem each addition solves
- Show why new complexity is necessary

When introducing a new concept, collapse or omit previously shown code to maintain focus on the new material.

Example of progressive complexity:

Step 1 - Core functionality:
```php
final class Project implements AggregateRoot
{
    private string $id;
    private string $name;

    public static function create(string $id, string $name): self
    {
        $project = new self();
        $project->apply(new ProjectCreated($id, $name));
        return $project;
    }

    protected function applyProjectCreated(ProjectCreated $event): void
    {
        $this->id = $event->id;
        $this->name = $event->name;
    }
}
```

Step 2 - Adding input validation:
```php
final class Project implements AggregateRoot
{
    // ... previous properties ...

    public static function create(string $id, string $name): self
    {
        if (empty($name)) {
            throw new InvalidArgumentException('Project name cannot be empty');
        }

        $project = new self();
        $project->apply(new ProjectCreated($id, $name));
        return $project;
    }

    // ... previous event handling ...
}
```

Step 3 - Adding state modification:
```php
final class Project implements AggregateRoot
{
    // ... previous properties ...

    public function rename(string $newName): void
    {
        if (empty($newName)) {
            throw new InvalidArgumentException('Project name cannot be empty');
        }

        $this->apply(new ProjectRenamed($this->id, $newName));
    }

    // ... previous methods ...
}
```

## Writing Style

### Technical Level
- Write for experienced software engineers
- Assume knowledge of:
  - Object-oriented programming
  - Design patterns
  - Testing practices
- Don't assume knowledge of:
  - Streak internals
  - Specific frameworks
  - Implementation details

### Code Examples
```php
// DO: Complete class with context
<?php

namespace Domain\Example;

use Streak\Domain\Command;
use Streak\Domain\Exception\ValidationFailed;

final class CreateExample implements Command
{
    public function __construct(
        private string $id,
        private string $name
    ) {
        if (empty($name)) {
            throw new ValidationFailed('Name cannot be empty');
        }
    }

    public function id(): string
    {
        return $this->id;
    }
}

// DON'T: Methods without context
public function handle($command): void
{
    $this->process($command);  // Where is process defined?
    $this->save();            // What's being saved?
}
```

### Code Example Guidelines
- Include complete namespace and imports
- Show full class context
- Keep examples focused and minimal
- Use real interfaces from the codebase
- Avoid duplicating code
- Ensure examples are accurate and reflect current best practices
- Avoid showing implementation details that belong in tutorials
- **Always consult Streak source files when using Streak code in examples**
  - Check actual interface definitions
  - Verify method signatures and return types
  - Use correct namespace paths
  - Match implementation details exactly
  - Example: If showing `EventStore` usage, check `streak/src/Domain/EventStore.php`
- **Use Streak's actual interfaces and implementation patterns**
  - DO: Implement Streak interfaces like `Query`, `Command`, `QueryHandler`, etc.
  - DO: Use Streak's method naming conventions (e.g., `handle()` instead of `__invoke()`)
  - DO: Follow Streak's patterns for query handling, command handling, and event processing
  - DON'T: Use generic implementations that don't reflect Streak's actual approach
  - Example: Query handlers should implement `Streak\Application\QueryHandler` and use `handle()` method
- **Avoid obvious code comments**
  - DON'T: `// Constructor: Initialize with ID and dependencies`
  - DON'T: `// Clock for timestamps (injected)`
  - DON'T: `// Save changes to repository`
  - DO: Only comment when explaining non-obvious behavior or important business rules
  - DO: Let the code be self-documenting through clear naming and structure
- **Use PHPDoc type hints when necessary**
  - DO: Add `/** @var Type $variable */` when type information cannot be inferred from context
  - DO: Include type hints in code examples that show variable usage without full class context
  - DO: Use type hints to clarify relationships between objects in examples
  - Example: `/** @var Repository $repository */` in repository usage examples
  - Example: `/** @var Project $project */` when showing aggregate manipulation

## Content Organization

### Document Structure
1. Overview - What and Why
2. Core Concepts - Key principles
3. Basic Usage - Simple examples
4. Advanced Topics - Complex scenarios
5. Related Concepts - Links and references

### Key Areas to Cover

#### Architecture
- Purpose of each pattern
- Component interactions
- System boundaries
- Consistency guarantees
- Failure handling

#### System Boundaries
- Component interfaces
- Transaction boundaries
- Error handling
- Cross-component communication

## Best Practices

### Do
- Start simple, add complexity gradually
- Show complete, working examples
- Link related concepts
- Document trade-offs
- Define clear boundaries
- Keep tutorials as standalone guides
- Ensure each document has a clear, focused purpose
- Avoid creating artificial dependencies between tutorials

### Don't
- Mix concepts with implementation
- Show incomplete code
- Duplicate information
- Oversimplify with analogies
- Include internal details in concepts
- Present tutorials as part of a series unless explicitly intended
- State the obvious or explain natural progressions
  - BAD: "As commands grow, they may need more data"
  - BAD: "Over time, you might want to add more validation"
  - BAD: "As your application grows..."
- Add examples that don't provide additional value

## Navigation and Cross-References

### Index Organization
- Group related concepts together
- Provide clear, concise descriptions for each document
- Avoid implying a required reading order unless necessary
- Present tutorials as standalone guides with their own value

### Cross-References
- Use relative links between documents
- Reference specific sections when appropriate
- Ensure links are accurate and maintained
- When encountering a broken link, find relevant existing documentation to link to rather than creating a new file
- Only create new files when the concept genuinely requires new documentation
- When linking to a specific topic, prefer linking to a specific section anchor (e.g., `file.md#section-name`) rather than just the file
- Create appropriate section headings (using `##` or `###`) to establish anchor points for important concepts
- Section anchors in Markdown follow the pattern: lowercase text with spaces replaced by hyphens (e.g., `## My Section` becomes `#my-section`)
