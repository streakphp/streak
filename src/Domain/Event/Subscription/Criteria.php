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

namespace Streak\Domain\Event\Subscription;

use Streak\Domain\Event\Subscription\Criteria\Visitor;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Criteria
{
    public function accept(Visitor $visitor);
}
