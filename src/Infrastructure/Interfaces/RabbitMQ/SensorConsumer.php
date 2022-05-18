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

namespace Streak\Infrastructure\Interfaces\RabbitMQ;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Streak\Application\Sensor;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Interfaces\RabbitMQ\SensorConsumerTest
 */
final class SensorConsumer implements ConsumerInterface
{
    private const ACK = true;
    private const NACK = false;

    public function __construct(private Sensor\Factory $factory)
    {
    }

    public function execute(AMQPMessage $msg)
    {
        $original = $msg->getBody();
        $msg = json_decode($original, true);

        if (\JSON_ERROR_NONE !== json_last_error()) {
            $msg = $original;
        }

        try {
            $sensor = $this->factory->create();
            $sensor->process($msg);
        } catch (\Exception) {
            return self::NACK;
        }

        return self::ACK;
    }
}
