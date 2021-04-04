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

namespace Streak\Infrastructure\RabbitMQ;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Streak\Application\Sensor;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\RabbitMQ\SensorConsumerTest
 */
final class SensorConsumer implements ConsumerInterface
{
    private Sensor\Factory $factory;

    public function __construct(Sensor\Factory $factory)
    {
        $this->factory = $factory;
    }

    public function execute(AMQPMessage $message)
    {
        $original = $message->getBody();
        $message = \json_decode($original, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            $message = $original;
        }

        try {
            $sensor = $this->factory->create();
            $sensor->process($message);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}
