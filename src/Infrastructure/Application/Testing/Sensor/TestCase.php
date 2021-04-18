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

namespace Streak\Infrastructure\Application\Testing\Sensor;

use Streak\Application\Sensor;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @codeCoverageIgnore
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    public function given(...$messages): Scenario\Then
    {
        return $this->createScenario()->given(...$messages);
    }

    abstract public function createFactory(): Sensor\Factory;

    private function createScenario(): Scenario
    {
        return new Scenario($this->createFactory());
    }
}
