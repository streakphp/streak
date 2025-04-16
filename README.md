![Streak](docs/images/logo.png)

# Streak PHP - Event Sourcing Framework

[![CI](https://github.com/streakphp/streak/actions/workflows/ci.yaml/badge.svg)](https://github.com/streakphp/streak/actions/workflows/ci.yaml)
[![codecov](https://codecov.io/gh/streakphp/streak/branch/master/graph/badge.svg)](https://codecov.io/gh/streakphp/streak)

Streak is a PHP framework designed for building applications using the Event Sourcing pattern. It provides a robust foundation for creating event-sourced, domain-driven applications with a focus on flexibility and testability.

## Features

- **Event Sourcing** - Persistent event store with support for various storage backends
- **Command Handling** - Type-safe command processing with built-in validation
- **Subscriptions** - Reliable event delivery system with support for resuming, restarting, and pausing
- **Projections** - Tools for building read models optimized for querying
- **Saga & Process Management** - Support for long-running business processes across multiple aggregates

## Installation

Install via Composer:

```bash
composer require streak/streak
```

For Symfony integration, install the Streak Bundle:

```bash
composer require streak/streak-bundle
```

## Documentation

Comprehensive documentation is available in the [docs](docs/index.md) directory:

- [Core Concepts](docs/index.md#core-concepts)
- [Getting Started](docs/index.md#getting-started)
- [Tutorials](docs/index.md#getting-started)
- [Symfony Integration](docs/symfony-bundle/installation.md)

## Development

### Requirements

- PHP 8.0+
- Docker and Docker Compose

### Running locally

Clone the repository:

```bash
git clone https://github.com/streakphp/streak.git
cd streak
```

Start the development environment:

```bash
docker-compose up --detach --build
```

### Running tests and checks

```bash
# Validate composer.json
docker-compose exec -T php composer validate --strict --no-interaction --ansi

# Install dependencies
docker-compose exec -T php composer install --no-scripts --no-interaction --ansi

# Run tests with coverage
docker-compose exec -T php xphp -dxdebug.mode=coverage bin/phpunit --color=always --configuration=phpunit.xml.dist

# Run tests without coverage
docker-compose run -T php bin/phpunit

# Check code quality
docker-compose exec -T php bin/rector --dry-run --ansi
docker-compose exec -T php bin/deptrac --no-interaction --cache-file=./build/.deptrac/.deptrac.cache --ansi
docker-compose exec -T php bin/php-cs-fixer fix --diff --dry-run --ansi --config=.php-cs-fixer.dist.php
```

## License

Streak is open-sourced software licensed under the [MIT license](LICENSE).
