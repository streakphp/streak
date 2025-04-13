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
 * @see \Streak\Domain\Exception\ConcurrentWriteDetectedTest
 */
class ConcurrentWriteDetected extends \RuntimeException
{
    public function __construct(private ?Domain\Id $id, \Throwable $previous = null)
    {
        $id ??= Domain\Id\UUID::random(); // TODO: fix it, remove weak dependency

        $message = \sprintf('Concurrent write detected when tried to persist "%s#%s" aggregate.', $id::class, $id->toString());

        parent::__construct($message, 0, $previous);
    }

    public function id(): ?Domain\Id
    {
        return $this->id;
    }
}
