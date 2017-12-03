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
use Streak\Infrastructure\Memento\InMemoryMemento;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class Scenario implements Scenario\Given, Scenario\When, Scenario\Then, Application\CommandHandler
{
    private $commands = [];
    private $bus;
    private $factory;
    private $memento;

    public function __construct(SynchronousCommandBus $bus, Application\Saga\Factory $factory)
    {
        $this->bus = $bus;
        $this->factory = $factory;
        $this->memento = new InMemoryMemento();

        $this->bus->register($this);
    }

    private function process(Domain\Message $message) : void
    {
        $saga = $this->factory->create();
        $saga->from($this->memento);
        $saga->onMessage($message);
        $saga->to($this->memento);
    }

    public function given(Domain\Message ...$messages) : Scenario\When
    {
        // TODO: check lifecycle

        foreach ($messages as $message) {
            $this->process($message);
        }

        $this->commands = []; // clear dispatched commands list up until this moment

        return $this;
    }

    public function when(Domain\Message $message) : Scenario\Then
    {
        // TODO: check lifecycle

        $this->process($message);

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
