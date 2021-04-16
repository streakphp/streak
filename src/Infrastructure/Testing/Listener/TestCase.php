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

namespace Streak\Infrastructure\Testing\Listener;

use Streak\Application;
use Streak\Domain;
use Streak\Domain\Event;
use Streak\Infrastructure\CommandBus\SynchronousCommandBus;
use Streak\Infrastructure\Testing\Listener;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @codeCoverageIgnore
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    public function given(Domain\Event ...$events): Scenario\When
    {
        $bus = new SynchronousCommandBus();
        $factory = $this->createFactory($bus);

        return $this
            ->createScenario($bus, $factory)
            ->given(...$events)
        ;
    }

    abstract public function createFactory(Application\CommandBus $bus): Event\Listener\Factory;

    private function createScenario(Application\CommandBus $bus, Event\Listener\Factory $factory): Listener\Scenario
    {
        return new Listener\Scenario($bus, $factory);
    }
}
