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

use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Converter
{
    /**
     * @throws Exception\ConversionToArrayNotPossible
     */
    public function eventToArray(Domain\Event $event) : array;

    /**
     * @throws Exception\ConversionToEventNotPossible
     */
    public function arrayToEvent(array $data) : Domain\Event;
}
