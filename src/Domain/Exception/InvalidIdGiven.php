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
 *
 * @see \Streak\Domain\Exception\InvalidIdGivenTest
 */
class InvalidIdGiven extends \InvalidArgumentException
{
    public function __construct(private Domain\Id $id, \Throwable $previous = null)
    {
        $message = 'Invalid aggregate id given.';

        parent::__construct($message, 0, $previous);
    }

    public function id(): Domain\Id
    {
        return $this->id;
    }
}
