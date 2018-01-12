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

namespace Streak\Infrastructure\Message\Converter;

use Streak\Domain\Message;
use Streak\Domain\Message\Converter;
use Streak\Domain\Message\Exception;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class CompositeConverter implements Converter
{
    /**
     * @var Converter[]
     */
    private $converters = [];

    public function __construct(Converter ...$converters)
    {
        $this->converters = $converters;
    }

    public function addConverter(Converter $converter) : void
    {
        $this->converters[] = $converter;
    }

    /**
     * @throws Exception\ConversionToArrayNotPossible
     */
    public function messageToArray(Message $message) : array
    {
        foreach ($this->converters as $converter) {
            try {
                return $converter->messageToArray($message);
            } catch (Exception\ConversionToArrayNotPossible $exception) {
                continue;
            } catch (\Exception $exception) {
                throw new Exception\ConversionToArrayNotPossible($message, $exception);
            }
        }

        throw new Exception\ConversionToArrayNotPossible($message);
    }

    /**
     * @throws Exception\ConversionToMessageNotPossible
     */
    public function arrayToMessage(string $class, array $data) : Message
    {
        $previous = null;
        foreach ($this->converters as $converter) {
            try {
                return $converter->arrayToMessage($class, $data);
            } catch (Exception\ConversionToMessageNotPossible $exception) {
                continue;
            } catch (\Exception $exception) {
                throw new Exception\ConversionToMessageNotPossible($class, $data, $exception);
            }
        }

        throw new Exception\ConversionToMessageNotPossible($class, $data);
    }
}
