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

namespace Streak\Infrastructure\Event\Converter;

use Streak\Domain\Event;
use Streak\Domain\Event\Converter;
use Streak\Domain\Event\Exception;

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
    public function eventToArray(Event $event) : array
    {
        foreach ($this->converters as $converter) {
            try {
                return $converter->eventToArray($event);
            } catch (Exception\ConversionToArrayNotPossible $exception) {
                continue;
            } catch (\Exception $exception) {
                throw new Exception\ConversionToArrayNotPossible($event, $exception);
            }
        }

        throw new Exception\ConversionToArrayNotPossible($event);
    }

    /**
     * @throws Exception\ConversionToEventNotPossible
     */
    public function arrayToEvent(array $data) : Event
    {
        $previous = null;
        foreach ($this->converters as $converter) {
            try {
                return $converter->arrayToEvent($data);
            } catch (Exception\ConversionToEventNotPossible $exception) {
                continue;
            } catch (\Exception $exception) {
                throw new Exception\ConversionToEventNotPossible($data, $exception);
            }
        }

        throw new Exception\ConversionToEventNotPossible($data);
    }
}
