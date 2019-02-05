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

namespace Streak\Domain\Id\Uuid;

use Streak\Domain\Id\Uuid;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Uuid5Factory
{
    /**
     * Generates deterministic UUID based on SHA1 of static namespace (also UUID) and name.
     *
     * @param Uuid   $namespace
     * @param string $name
     *
     * @return Uuid
     */
    public function generateUuid5(Uuid $namespace, string $name) : Uuid;
}
