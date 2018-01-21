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
 */
abstract class ConversionNotPossible extends \InvalidArgumentException
{
    public function __construct(\Throwable $previous = null)
    {
        parent::__construct('Conversion not possible.', 0, $previous);
    }
}
