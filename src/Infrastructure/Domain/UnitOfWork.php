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

namespace Streak\Infrastructure\Domain;

use Streak\Infrastructure\Domain\UnitOfWork\Exception;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface UnitOfWork
{
    /**
     * @throws Exception\ObjectNotSupported
     */
    public function add(object $object): void;

    public function remove(object $object): void;

    public function has(object $object): bool;

    /**
     * @return array<int, object>
     */
    public function uncommitted(): array;

    public function count(): int;

    /**
     * @return \Generator<int, object>
     */
    public function commit(): \Generator;

    public function clear(): void;
}
