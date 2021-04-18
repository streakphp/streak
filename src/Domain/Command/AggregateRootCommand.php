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

namespace Streak\Domain\Command;

use Streak\Domain\AggregateRoot;
use Streak\Domain\Command;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface AggregateRootCommand extends Command
{
    public function aggregateRootId(): AggregateRoot\Id;
}
