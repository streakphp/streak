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
    public function objectToArray($object) : array
    {
        foreach ($this->converters as $converter) {
            try {
                return $converter->objectToArray($object);
            } catch (Exception\ConversionToArrayNotPossible $exception) {
                continue;
            } catch (\Exception $exception) {
                throw new Exception\ConversionToArrayNotPossible($object, $exception);
            }
        }

        throw new Exception\ConversionToArrayNotPossible($object);
    }

    /**
     * @throws Exception\ConversionToObjectNotPossible
     */
    public function arrayToObject(array $data)
    {
        $previous = null;
        foreach ($this->converters as $converter) {
            try {
                return $converter->arrayToObject($data);
            } catch (Exception\ConversionToObjectNotPossible $exception) {
                continue;
            } catch (\Exception $exception) {
                throw new Exception\ConversionToObjectNotPossible($data, $exception);
            }
        }

        throw new Exception\ConversionToObjectNotPossible($data);
    }
}
