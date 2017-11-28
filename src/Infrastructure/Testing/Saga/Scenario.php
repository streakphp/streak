<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Infrastructure\Testing\Saga;

use PHPUnit\Framework\Assert;
use Streak\Application;
use Streak\Domain;
use Streak\Infrastructure\CommandHandler\SynchronousCommandBus;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class Scenario implements Scenario\Given, Scenario\When, Scenario\Then, Application\CommandHandler
{
    /**
     * @var Application\Command[]
     */
    private $commands = [];
    private $bus;
    private $saga;

    public function __construct(SynchronousCommandBus $bus, Application\Saga $saga)
    {
        $this->bus = $bus;
        $this->saga = $saga;

        $this->bus->register($this);
    }

    public function given(Domain\Message ...$messages) : Scenario\When
    {
        foreach ($messages as $message) {
            $this->saga->onMessage($message);
        }

        $this->commands = []; // clear dispatched commands list up until this moment

        return $this;
    }

    public function when(Domain\Message $message) : Scenario\Then
    {
        $this->saga->onMessage($message);

        return $this;
    }

    public function then(Application\Command $expected) : void
    {
        Assert::assertEquals([$expected], $this->commands);
    }

    public function handle(Application\Command $command) : void
    {
        $this->commands[] = $command;
    }
}
