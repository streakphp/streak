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

namespace Streak\Infrastructure\Domain\Event\Subscription\DAO;

use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Subscription;
use Streak\Infrastructure\Domain\Event\Subscription\DAO;

/**
 * @see \Streak\Infrastructure\Domain\Event\Subscription\DAO\IdentityMappingDaoTest
 */
class IdentityMappingDao implements DAO
{
    /** @var int[] */
    private array $versions = [];

    public function __construct(private DAO $dao)
    {
    }

    public function save(Subscription $subscription): void
    {
        if ($this->shouldSave($subscription)) {
            $this->dao->save($subscription);
            $this->rememberVersion($subscription);
        }
    }

    public function one(Listener\Id $id): ?Subscription
    {
        $subscription = $this->dao->one($id);

        if (null !== $subscription) {
            $this->rememberVersion($subscription);
        }

        return $subscription;
    }

    public function exists(Listener\Id $id): bool
    {
        return $this->dao->exists($id);
    }

    /**
     * {@inheritdoc}
     */
    public function all(array $types = [], ?bool $completed = null): iterable
    {
        foreach ($this->dao->all($types, $completed) as $subscription) {
            $this->rememberVersion($subscription);

            yield $subscription;
        }
    }

    public function shouldSave(Subscription $subscription): bool
    {
        if (false === $this->isVersionRemembered($subscription)) {
            return true;
        }

        $previous = $this->rememberedVersion($subscription);
        $current = $subscription->version();

        if ($previous < $current) {
            return true;
        }

        return false;
    }

    private function key(Subscription $subscription): string
    {
        return sprintf('%s_%s', $subscription->id()::class, $subscription->id()->toString());
    }

    private function rememberVersion(Subscription $subscription): void
    {
        $this->versions[$this->key($subscription)] = $subscription->version();
    }

    private function isVersionRemembered(Subscription $subscription): bool
    {
        return \array_key_exists($this->key($subscription), $this->versions);
    }

    private function rememberedVersion(Subscription $subscription): int
    {
        return $this->versions[$this->key($subscription)];
    }
}
