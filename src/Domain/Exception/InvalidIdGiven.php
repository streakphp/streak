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

namespace Streak\Domain\Exception;

use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class InvalidIdGiven extends \InvalidArgumentException
{
    private $id;

    public function __construct(Domain\Id $id, \Throwable $previous = null)
    {
        $this->id = $id;

        $message = sprintf('Invalid aggregate id given.');

        parent::__construct($message, 0, $previous);
    }

    public function id() : Domain\Id
    {
        return $this->id;
    }
}
