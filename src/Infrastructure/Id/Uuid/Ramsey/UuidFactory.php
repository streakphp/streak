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

namespace Streak\Infrastructure\Id\Uuid\Ramsey;

use Streak\Domain\Id\Uuid;
use Streak\Domain\Id\Uuid\Uuid4Factory;
use Streak\Domain\Id\Uuid\Uuid5Factory;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class UuidFactory implements Uuid4Factory, Uuid5Factory
{
    public function generateUuid4() : Uuid
    {
        $uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();

        return new Uuid($uuid);
    }

    public function generateUuid5(Uuid $namespace, string $name) : Uuid
    {
        $uuid = \Ramsey\Uuid\Uuid::uuid5($namespace->toString(), $name)->toString();

        return new Uuid($uuid);
    }
}
