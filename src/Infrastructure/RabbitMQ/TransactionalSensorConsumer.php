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
use Streak\Infrastructure\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\RabbitMQ\TransactionalSensorConsumerTest
 */
class TransactionalSensorConsumer implements ConsumerInterface
{
    private ConsumerInterface $consumer;
    private UnitOfWork $uow;

    public function __construct(ConsumerInterface $consumer, UnitOfWork $uow)
    {
        $this->consumer = $consumer;
        $this->uow = $uow;
    }

    public function execute(AMQPMessage $message)
    {
        $this->uow->clear();

        try {
            $result = $this->consumer->execute($message);

            if (false !== $result) {
                iterator_to_array($this->uow->commit());
            }

            return $result;
        } finally {
            $this->uow->clear();
        }
    }
}
