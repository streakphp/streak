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

use InvalidArgumentException;

/**
 * @see \Streak\Domain\Event\Exception\NotSupportedTypeTest
 */
final class NotSupportedType extends InvalidArgumentException
{
    /** @var mixed */
    private $value;

    public function __construct($value)
    {
        if (\is_callable($value)) {
            $type = 'callable';
        } else {
            $type = \gettype($value);
        }
        parent::__construct(sprintf('Type %s is not supported for conversion!', $type));
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }
}
