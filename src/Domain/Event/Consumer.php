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

use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Consumer
{
    /**
     * @throws Event\Exception\NoEventApplyingMethodFound
     * @throws Event\Exception\TooManyEventApplyingMethodsFound
     */
    public function applyEvent(Event\Envelope $event): void;
}
