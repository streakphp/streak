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
 *
 * @template T of object
 */
interface UnitOfWork
{
    /**
     * @param T $object
     *
     * @throws Exception\ObjectNotSupported
     */
    public function add(object $object): void;

    /**
     * @param T $object
     */
    public function remove(object $object): void;

    /**
     * @param T $object
     */
    public function has(object $object): bool;

    /**
     * @return array<int, T>
     */
    public function uncommitted(): array;

    public function count(): int;

    /**
     * @return \Generator<int, T>
     */
    public function commit(): \Generator;

    public function clear(): void;
}
