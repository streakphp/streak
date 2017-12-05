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
use Streak\Infrastructure\Persistable\InMemoryState;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class Scenario implements Scenario\Given, Scenario\When, Scenario\Then, Application\CommandHandler
{
    private $commands = [];
    private $messages = [];
    private $factory;
    private $state;

    public function __construct(SynchronousCommandBus $bus, Application\Saga\Factory $factory)
    {
        $this->factory = $factory;
        $this->state = new InMemoryState();

        $bus->register($this);
    }

    private function process(Domain\Message $message) : void
    {
        $saga = $this->factory->create();
        $saga->from($this->state);
        $saga->onMessage($message);
        $saga->to($this->state);
    }

    public function given(Domain\Message ...$messages) : Scenario\When
    {
        $this->messages = $messages;

        foreach ($messages as $message) {
            $this->process($message);
        }

        $this->commands = []; // clear dispatched commands list up until this moment

        return $this;
    }

    public function when(Domain\Message $message) : Scenario\Then
    {
        $this->messages[] = $message;

        $this->process($message);

        return $this;
    }

    public function then(Application\Command $expected) : void
    {
        $first = array_shift($this->messages);
        $last = array_pop($this->messages);

        Assert::assertTrue($this->isMessageStartingSaga($first), 'Saga should begin its lifecycle with first given message.');

        // iterate on messages between first and last message
        foreach ($this->messages as $message) {
            Assert::assertFalse($this->isMessageEndingSaga($message), 'Saga should not end its lifecycle before all messages were listen.');
        }

        Assert::assertEquals([$expected], $this->commands);
    }

    public function handle(Application\Command $command) : void
    {
        $this->commands[] = $command;
    }

    private function isMessageStartingSaga(Domain\Message $message) : bool
    {
        $saga = $this->factory->create();

        return $saga::beginsWith($message);
    }

    private function isMessageEndingSaga(Domain\Message $message) : bool
    {
        $saga = $this->factory->create();

        return $saga::endsWith($message);
    }
}
