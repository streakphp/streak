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

namespace Streak\Domain\Message;

use Streak\Domain\Message;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Converter
{
    /**
     * @throws Exception\ConversionToArrayNotPossible
     */
    public function messageToArray(Message $message) : array;

    /**
     * @throws Exception\ConversionToMessageNotPossible
     */
    public function arrayToMessage(string $class, array $data) : Message;
}
