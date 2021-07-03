<?php

/**
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Rector\CodeQuality\Rector\FuncCall\SingleInArrayToCompareRector;
use Rector\CodeQuality\Rector\FuncCall\UnwrapSprintfOneArgumentRector;
use Rector\Core\Configuration\Option;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator): void {
    // get parameters
    $parameters = $configurator->parameters();

    // Define what rule sets will be applied
    $configurator->import(PHPUnitSetList::PHPUNIT_91);
    $configurator->import(PHPUnitSetList::PHPUNIT_CODE_QUALITY);
    $configurator->import(PHPUnitSetList::PHPUNIT_EXCEPTION);
    $configurator->import(PHPUnitSetList::PHPUNIT_MOCK);
    $configurator->import(PHPUnitSetList::PHPUNIT_SPECIFIC_METHOD);
    $configurator->import(PHPUnitSetList::PHPUNIT_YIELD_DATA_PROVIDER);
//    $configurator->import(SetList::CODE_QUALITY);
    $configurator->import(SetList::EARLY_RETURN);
    $configurator->import(SetList::PHP_74);
    $configurator->import(SetList::PHP_80);
//    $configurator->import(SetList::PRIVATIZATION);

    $parameters->set(Option::SKIP, [
    ]);

    // skip root namespace classes, like \DateTime or \Exception [default: true]
    $parameters->set(Option::IMPORT_SHORT_CLASSES, true);

    // paths to refactor; solid alternative to CLI arguments
    $parameters->set(Option::PATHS, [
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    // Run Rector only on changed files
    $parameters->set(Option::ENABLE_CACHE, true);
    $parameters->set(Option::CACHE_DIR, __DIR__ . '/build/.rector');

    $services = $configurator->services();

    $services->set(UnwrapSprintfOneArgumentRector::class);
    $services->set(SingleInArrayToCompareRector::class);
};
