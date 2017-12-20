<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Streak\Infrastructure\Testing\Saga;

use Streak\Application;
use Streak\Domain;
use Streak\Infrastructure\CommandBus\SynchronousCommandBus;
use Streak\Infrastructure\Testing\Saga;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @codeCoverageIgnore
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    private $bus;

    public function setUp()
    {
        $this->bus = new SynchronousCommandBus();
    }

    private function createScenario() : Saga\Scenario
    {
        return new Saga\Scenario($this->getCommandBus(), $this->createFactory($this->getCommandBus()));
    }

    public function getCommandBus() : SynchronousCommandBus
    {
        return $this->bus;
    }

    public function given(Domain\Message ...$messages) : Scenario\When
    {
        return $this->createScenario()->given(...$messages);
    }

    abstract public function createFactory(Application\CommandBus $bus) : Application\Saga\Factory;
}
