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

namespace Streak\Infrastructure\Id\Uuid;

use Streak\Domain\Id\Uuid;
use Streak\Domain\Id\Uuid\Uuid4Factory;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class TestUuid4Factory implements Uuid4Factory
{
    private $uuids = [];

    public function __construct(Uuid... $uuids)
    {
        $this->uuids = $uuids;
    }

    public function generateUuid4() : Uuid
    {
        $uuid = array_shift($this->uuids);

        return $uuid;
    }
}
