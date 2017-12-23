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

namespace Streak\Domain\Exception;

use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class MessagesNotPublished extends \RuntimeException
{
    private $messages;

    public function __construct(Domain\Message ...$messages)
    {
        $this->messages = $messages;

        parent::__construct('Messages not published.');
    }

    /**
     * @return Domain\Message[]
     */
    public function messages() : array
    {
        return $this->messages;
    }
}
