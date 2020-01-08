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

namespace Streak\Infrastructure\Event\Envelope;

use SebastianBergmann\Comparator\ComparisonFailure;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class Comparator extends \SebastianBergmann\Comparator\Comparator
{
    public function accepts($expected, $actual)
    {
        if ($expected instanceof Event\Envelope && $actual instanceof Event\Envelope) {
            return true;
        }

        if ($expected instanceof Event\Envelope && $actual instanceof Event) {
            return true;
        }

        if ($expected instanceof Event && $actual instanceof Event\Envelope) {
            return true;
        }

        return false;
    }

    public function assertEquals($expected, $actual, $delta = 0.0, $canonicalize = false, $ignoreCase = false)
    {
        if ($expected instanceof Event\Envelope && $actual instanceof Event\Envelope) {
            if (false === $expected->equals($actual)) {
                throw new ComparisonFailure(
                    $expected,
                    $actual,
                    $expected->uuid()->toString(),
                    $actual->uuid()->toString()
                );
            }

            return;
        }

        if ($expected instanceof Event\Envelope) {
            $expected = $expected->message();
        }

        if ($actual instanceof Event\Envelope) {
            $actual = $actual->message();
        }

        // compare events contained within envelopes
        $comparator = $this->factory->getComparatorFor($expected, $actual);
        $comparator->assertEquals($expected, $actual, $delta, $canonicalize, $ignoreCase);
    }
}
