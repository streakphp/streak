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

namespace Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Converter
{
    /**
     * @throws Exception\ConversionToArrayNotPossible
     */
    public function objectToArray(object $object): array;

    /**
     * @throws Exception\ConversionToObjectNotPossible
     */
    public function arrayToObject(array $data): object;
}
