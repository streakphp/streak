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

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class ConversionToMessageNotPossible extends ConversionNotPossible
{
    private $targetClass;
    private $givenArray;

    public function __construct(string $targetClass, array $givenArray, \Throwable $previous = null)
    {
        $this->targetClass = $targetClass;
        $this->givenArray = $givenArray;

        parent::__construct($previous);
    }

    public function targetClass() : string
    {
        return $this->targetClass;
    }

    public function givenArray() : array
    {
        return $this->givenArray;
    }
}
