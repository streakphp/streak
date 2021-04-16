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

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Domain\Exception\ObjectNotSupportedTest
 */
class ObjectNotSupported extends \RuntimeException
{
    private object $object;

    public function __construct(object $object, \Throwable $previous = null)
    {
        $this->object = $object;

        $message = 'Object is not supported.';

        parent::__construct($message, 0, $previous);
    }

    public function object(): object
    {
        return $this->object;
    }
}
