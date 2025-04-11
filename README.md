![Streak](docs/images/logo.png)

-------------------------------

[![CI](https://github.com/streakphp/streak/actions/workflows/ci.yaml/badge.svg)](https://github.com/streakphp/streak/actions/workflows/ci.yaml)
[![codecov](https://codecov.io/gh/streakphp/streak/branch/master/graph/badge.svg)](https://codecov.io/gh/streakphp/streak)

For more information and usage instructions, please refer to the [**Documentation**](docs/index.md).

Running checks & tests locally
------------------------------

`docker-compose up --detach --build`

`docker-compose exec -T php composer validate --strict --no-interaction --ansi`

`docker-compose exec -T php composer install --no-scripts --no-interaction --ansi`

`docker-compose exec -T php xphp -dxdebug.mode=coverage bin/phpunit --color=always --configuration=phpunit.xml.dist`

`docker-compose run  -T php bin/phpunit`

`docker-compose exec -T php bin/rector --dry-run --ansi`

`docker-compose exec -T php bin/deptrac --no-interaction --cache-file=./build/.deptrac/.deptrac.cache --ansi`

`docker-compose exec -T php bin/php-cs-fixer fix --diff --dry-run --ansi --config=.php-cs-fixer.dist.php`
