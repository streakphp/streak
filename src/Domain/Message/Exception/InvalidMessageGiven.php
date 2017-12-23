<?php

declare(strict_types=1);

/**
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain\Message\Exception;

use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class InvalidMessageGiven extends \InvalidArgumentException
{
    private $givenMessage;

    public function __construct(Domain\Message $givenMessage, \Throwable $previous = null)
    {
        $this->givenMessage = $givenMessage;

        parent::__construct('Invalid message given.', 0, $previous);
    }

    public function givenMessage() : Domain\Message
    {
        return $this->givenMessage;
    }
}
