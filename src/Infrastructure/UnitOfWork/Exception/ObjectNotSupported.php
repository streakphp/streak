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

namespace Streak\Infrastructure\UnitOfWork\Exception;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class ObjectNotSupported extends \RuntimeException
{
    private $object;

    /**
     * @param object $object
     */
    public function __construct($object, \Throwable $previous = null)
    {
        $this->object = $object;

        $message = sprintf('Object is not supported.');

        parent::__construct($message, 0, $previous);
    }

    /**
     * @return object
     */
    public function object()
    {
        return $this->object;
    }
}
