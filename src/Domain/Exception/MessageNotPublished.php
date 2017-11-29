<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain\Exception;

use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class MessageNotPublished extends \RuntimeException
{
    private $message;

    public function __construct(Domain\Message $message, \Throwable $previous = null)
    {
        $this->message = $message;

        $error = sprintf('Message "%s" not published.', \get_class($message));

        parent::__construct($error, 0, $previous);
    }

    public function message() : Domain\Message
    {
        return $this->message;
    }
}
