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

namespace Streak\Infrastructure\Sensor;

use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Streak\Application;
use Streak\Application\Sensor;
use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class LoggingSensor implements Application\Sensor
{
    private $sensor;
    private $logger;

    public function __construct(Application\Sensor $sensor, LoggerInterface $logger)
    {
        $this->sensor = $sensor;
        $this->logger = $logger;
    }

    public function producerId() : Domain\Id
    {
        return $this->sensor->producerId();
    }

    public function events() : array
    {
        return $this->sensor->events();
    }

    public function sensorId() : Sensor\Id
    {
        return $this->sensor->sensorId();
    }

    public function process(...$messages) : void
    {
        try {
            $this->sensor->process(...$messages);
        } catch (\TypeError $exception) {
            foreach($messages as $message) {
                $this->logger->critical(
                    'Sensor "{sensor}" has thrown "{class}" exception with "{exceptionMessage}" message while processing following message {message}.',
                    [
                        'sensor' => get_class($this->sensor),
                        'class' => get_class($exception),
                        'exceptionMessage' => $exception->getMessage(),
                        'message' => $message instanceof AMQPMessage ? $message->getBody() : $message
                    ]
                );
            }

            throw $exception;
        } catch (\Throwable $exception) {
            $this->logger->debug('Sensor "{sensor}" has thrown "{class}" exception with "{message}" message while processing messages.', [
                'sensor' => get_class($this->sensor),
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }
}
