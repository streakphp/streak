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

namespace Streak\Application\Saga;

use Streak\Application\Saga;
use Streak\Domain\Event\Listener;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
trait Identification
{
    use Listener\Identification {
        Listener\Identification::identifyBy as private identifyListenerBy;
    }

    public function __construct(Saga\Id $id)
    {
        $this->identifyBy($id);
    }

    public function sagaId() : Saga\Id
    {
        return $this->id;
    }

    protected function identifyBy(Saga\Id $id) : void
    {
        $this->identifyListenerBy($id);
    }
}
