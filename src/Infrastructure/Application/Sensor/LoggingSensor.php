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

namespace Streak\Infrastructure\Application\Sensor;

use Psr\Log\LoggerInterface;
use Streak\Application;
use Streak\Application\Sensor;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Application\Sensor\LoggingSensorTest
 */
class LoggingSensor implements Application\Sensor
{
    public function __construct(private Application\Sensor $sensor, private LoggerInterface $logger)
    {
    }

    public function id(): Sensor\Id
    {
        return $this->sensor->id();
    }

    public function events(): array
    {
        return $this->sensor->events();
    }

    public function process(...$messages): void
    {
        try {
            $this->sensor->process(...$messages);
        } catch (\Throwable $exception) {
            $this->logger->debug('Sensor "{sensor}" has thrown "{class}" exception with "{message}" message while processing messages.', [
                'sensor' => $this->sensor::class,
                'class' => $exception::class,
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }
}
