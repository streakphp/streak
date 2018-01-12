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

namespace Streak\Domain\Message\Exception;

use Streak\Domain\Message;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class ConversionToArrayNotPossible extends ConversionNotPossible
{
    private $givenMessage;

    public function __construct(Message $givenMessage, \Throwable $previous = null)
    {
        $this->givenMessage = $givenMessage;

        parent::__construct($previous);
    }

    public function givenMessage() : Message
    {
        return $this->givenMessage;
    }
}
