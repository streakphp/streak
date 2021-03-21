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

namespace Streak\Infrastructure\Testing\Sensor;

use PHPUnit\Framework\Assert;
use Streak\Application\Sensor;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @codeCoverageIgnore
 */
class Scenario implements Scenario\Given, Scenario\Then
{
    private Sensor $sensor;

    public function __construct(Sensor\Factory $factory)
    {
        $this->sensor = $factory->create();
    }

    public function given(...$messages) : Scenario\Then
    {
        $this->sensor->process(...$messages);

        return $this;
    }

    public function then(Event ...$expected) : void
    {
        $actual = array_map(fn (Event\Envelope $event) => $event->message(), $this->sensor->events());
        Assert::assertEquals($expected, $actual, 'Expected events don\'t match produced events.');
    }
}
