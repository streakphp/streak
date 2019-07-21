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

namespace Streak\Infrastructure\Testing\Listener\Scenario;

use Streak\Application;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Then
{
    public function then(Application\Command $command = null, \Throwable $error = null) : Then;

    public function assert(callable $constraint = null) : void;
}
