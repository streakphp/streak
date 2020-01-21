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

namespace Streak\Infrastructure\Event\Subscription\DAO;

use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Subscription;
use Streak\Infrastructure\Event\Subscription\DAO;

class IdentityMappingDao implements DAO
{
    /** @var DAO */
    private $dao;

    /** @var Event\Envelope[] */
    private $lastProcessedEvents;

    public function __construct(DAO $dao)
    {
        $this->dao = $dao;
        $this->lastProcessedEvents = [];
    }

    public function save(DAO\Subscription $subscription) : void
    {
        if ($this->shouldSave($subscription)) {
            $this->dao->save($subscription);
            $this->rememberLastProcessedEvent($subscription);
        }
    }

    public function one(Listener\Id $id) : ?Subscription
    {
        $subscription = $this->dao->one($id);
        if (null !== $subscription) {
            $this->rememberLastProcessedEvent($subscription);
        }

        return $subscription;
    }

    public function exists(Listener\Id $id) : bool
    {
        return $this->dao->exists($id);
    }

    /**
     * {@inheritdoc}
     */
    public function all(array $types = [], ?bool $completed = null) : iterable
    {
        foreach ($this->dao->all($types, $completed) as $subscription) {
            $this->rememberLastProcessedEvent($subscription);
            yield $subscription;
        }
    }

    public function shouldSave(Subscription $subscription) : bool
    {
        if (false === $this->hasLastRememberedEvent($subscription)) {
            return true;
        }

        $subscriptionLastProcessedEvent = $subscription->lastProcessedEvent();
        $thisLastRememberedEvent = $this->getLastRememberedEvent($subscription);

        if (null === $subscriptionLastProcessedEvent) {
            return $subscriptionLastProcessedEvent !== $thisLastRememberedEvent;
        }

        return false === $subscriptionLastProcessedEvent->equals($thisLastRememberedEvent);
    }

    private function createKey(Subscription $subscription) : string
    {
        return sprintf('%s_%s', get_class($subscription->subscriptionId()), $subscription->subscriptionId()->toString());
    }

    private function rememberLastProcessedEvent(Subscription $subscription)
    {
        $this->lastProcessedEvents[$this->createKey($subscription)] = $subscription->lastProcessedEvent();
    }

    private function hasLastRememberedEvent(Subscription $subscription) : bool
    {
        return array_key_exists($this->createKey($subscription), $this->lastProcessedEvents);
    }

    private function getLastRememberedEvent(Subscription $subscription) : ?Event\Envelope
    {
        return $this->lastProcessedEvents[$this->createKey($subscription)];
    }
}
