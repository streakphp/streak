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
    private $bus;
    private $scenarioExecuted;

    public function setUp()
    {
        $this->bus = new SynchronousCommandBus();
        $this->scenarioExecuted = false;
    }

    public function getCommandBus() : SynchronousCommandBus
    {
        return $this->bus;
    }

    public function given(Domain\Event ...$events) : Scenario\When
    {
        if (true === $this->scenarioExecuted) {
            $message = 'Scenario already executed.';
            throw new \BadMethodCallException($message);
        }

        $this->scenarioExecuted = true;

        return $this->createScenario()->given(...$events);
    }

    abstract public function createFactory(Application\CommandBus $bus) : Event\Listener\Factory;

    private function createScenario() : Listener\Scenario
    {
        return new Listener\Scenario($this->getCommandBus(), $this->createFactory($this->getCommandBus()));
    }
}
