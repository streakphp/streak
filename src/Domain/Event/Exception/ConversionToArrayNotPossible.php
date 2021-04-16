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

namespace Streak\Domain\Event\Exception;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Domain\Event\Exception\ConversionToArrayNotPossibleTest
 */
class ConversionToArrayNotPossible extends ConversionNotPossible
{
    private object $object;

    public function __construct($object, \Throwable $previous = null)
    {
        $this->object = $object;

        parent::__construct($previous);
    }

    public function object(): object
    {
        return $this->object;
    }
}
