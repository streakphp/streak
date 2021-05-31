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

namespace Streak\Infrastructure\Domain\Event\Sourced\Subscription;

use PHPUnit\Framework\TestCase;
use Streak\Application\Event\Listener\State;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\Event\Sourced\Subscription\InMemoryState
 */
class InMemoryStateTest extends TestCase
{
    /**
     * Stub acting as ['attribute-1' => 'value-1'] state.
     */
    private State $stub;

    protected function setUp(): void
    {
        $this->stub = new class() implements State {
            public function equals(object $object): bool
            {
                throw new \BadMethodCallException('Do not call method State::equals() on this stub.');
            }

            public function has(string $name): bool
            {
                return 'attribute-1' === $name;
            }

            public function get(string $name)
            {
                if ('attribute-1' === $name) {
                    return 'value-1';
                }

                throw new \OutOfBoundsException();
            }

            public function toArray(): array
            {
                return ['attribute-1' => 'value-1'];
            }

            public function set(string $name, $value): State
            {
                throw new \BadMethodCallException('Do not call method State::set() on this stub.');
            }
        };
    }

    public function testState(): void
    {
        $state1a = InMemoryState::empty();
        $state2a = InMemoryState::empty();

        self::assertFalse($state1a->equals(new \stdClass()));
        self::assertFalse($state2a->equals(new \stdClass()));

        self::assertFalse($state1a->equals($this->stub));
        self::assertFalse($state2a->equals($this->stub));

        self::assertTrue($state1a->equals($state1a));
        self::assertTrue($state2a->equals($state2a));
        self::assertTrue($state1a->equals($state2a));
        self::assertTrue($state2a->equals($state1a));

        self::assertSame([], $state1a->toArray());
        self::assertSame([], $state2a->toArray());

        self::assertFalse($state1a->has('attribute-1'));
        self::assertFalse($state2a->has('attribute-1'));
        self::assertFalse($state1a->has('attribute-2'));
        self::assertFalse($state2a->has('attribute-2'));

        $state1b = $state1a->set('attribute-1', 'value-1');

        self::assertTrue($state1b->equals($this->stub)); // although states are both of different types they contain same data.

        self::assertNotSame($state1a, $state1b);
        self::assertFalse($state1a->has('attribute-1'));
        self::assertSame([], $state1a->toArray());
        self::assertTrue($state1b->has('attribute-1'));
        self::assertFalse($state1b->has('attribute-2'));
        self::assertSame(['attribute-1' => 'value-1'], $state1b->toArray());
        self::assertSame('value-1', $state1b->get('attribute-1'));

        $state1c = $state1a->set('attribute-1', 'value-1');

        self::assertTrue($state1c->equals($this->stub)); // although states are both of different types they contain same data.

        self::assertNotSame($state1a, $state1c);
        self::assertFalse($state1a->has('attribute-1'));
        self::assertSame([], $state1a->toArray());
        self::assertTrue($state1c->has('attribute-1'));
        self::assertFalse($state1c->has('attribute-2'));
        self::assertSame(['attribute-1' => 'value-1'], $state1c->toArray());
        self::assertSame('value-1', $state1c->get('attribute-1'));

        $state1d = $state1c->set('attribute-2', ['attribute-1' => 'value-1', 'attribute-2' => 'value-2']);

        self::assertFalse($state1d->equals($this->stub)); // although states are both of different types they contain same data.

        self::assertNotSame($state1c, $state1d);
        self::assertTrue($state1c->has('attribute-1'));
        self::assertTrue($state1c->has('attribute-1'));
        self::assertSame(['attribute-1' => 'value-1'], $state1c->toArray());
        self::assertTrue($state1d->has('attribute-1'));
        self::assertTrue($state1d->has('attribute-2'));
        self::assertSame(['attribute-1' => 'value-1', 'attribute-2' => ['attribute-1' => 'value-1', 'attribute-2' => 'value-2']], $state1d->toArray());
        self::assertSame('value-1', $state1d->get('attribute-1'));
        self::assertSame(['attribute-1' => 'value-1', 'attribute-2' => 'value-2'], $state1d->get('attribute-2'));

        $state1e = $state1d->set('attribute-2', 'value-3');

        self::assertFalse($state1d->equals($this->stub)); // although states are both of different types they contain same data.

        self::assertNotSame($state1c, $state1d);
        self::assertFalse($state1e->equals($state1d));

        $state3a = InMemoryState::fromArray(['attribute-2' => ['attribute-2' => 'value-2', 'attribute-1' => 'value-1'], 'attribute-1' => 'value-1']); // notice reverted order of keys

        self::assertTrue($state3a->has('attribute-1'));
        self::assertTrue($state3a->has('attribute-2'));
        self::assertSame(['attribute-1' => 'value-1', 'attribute-2' => ['attribute-1' => 'value-1', 'attribute-2' => 'value-2']], $state3a->toArray());
        self::assertSame('value-1', $state3a->get('attribute-1'));
        self::assertSame(['attribute-1' => 'value-1', 'attribute-2' => 'value-2'], $state3a->get('attribute-2'));

        self::assertTrue($state3a->equals($state1d));
        self::assertTrue($state1d->equals($state3a));

        self::assertFalse($state3a->equals($state1a));
        self::assertFalse($state3a->equals($state1b));
        self::assertFalse($state3a->equals($state1c));
        self::assertFalse($state3a->equals($state2a));
        self::assertFalse($state1a->equals($state3a));
        self::assertFalse($state1b->equals($state3a));
        self::assertFalse($state1c->equals($state3a));
        self::assertFalse($state2a->equals($state3a));

        $state4a = InMemoryState::fromState($state3a);

        self::assertTrue($state4a->equals($state3a));
        self::assertTrue($state3a->equals($state4a));

        self::assertTrue($state3a->equals($state1d));
        self::assertTrue($state1d->equals($state3a));

        self::assertFalse($state3a->equals($state1a));
        self::assertFalse($state3a->equals($state1b));
        self::assertFalse($state3a->equals($state1c));
        self::assertFalse($state3a->equals($state2a));
        self::assertFalse($state1a->equals($state3a));
        self::assertFalse($state1b->equals($state3a));
        self::assertFalse($state1c->equals($state3a));
        self::assertFalse($state2a->equals($state3a));
    }

    public function testGettingMissingName(): void
    {
        $this->expectException(\OutOfBoundsException::class);

        $state = InMemoryState::empty();
        $state->get('missing-name');
    }

    public function testSettingInvalidName(): void
    {
        $this->expectException(\OutOfBoundsException::class);

        $state = InMemoryState::empty();
        $state->set('', 'value-1');
    }

    /**
     * @dataProvider validValues
     *
     * @param mixed $value
     */
    public function testSettingValidValues($value): void
    {
        $state = InMemoryState::empty();
        $result = $state->set('name', $value);

        self::assertInstanceOf(InMemoryState::class, $result);
        self::assertNotEquals($state, $result);
        self::assertNotSame($state, $result);

        self::assertSame($value, $result->get('name'));
    }

    /**
     * @dataProvider invalidValues
     *
     * @param mixed $value
     */
    public function testSettingInvalidValue($value): void
    {
        $this->expectException(\UnexpectedValueException::class);

        $state = InMemoryState::empty();
        $state->set('name', $value);
    }

    public function validValues()
    {
        return [
            [null],
            [1],
            [-1],
            [0],
            [0.0],
            [1.7976931348623e+308],
            [2.2250738585072e-308],
            [\PHP_INT_MAX],
            [\PHP_INT_MIN],
            [''],
            ['value'],
            [[null, 1, -1, 0, 0.0, 1.7976931348623e+308, 2.2250738585072e-308, \PHP_INT_MAX, \PHP_INT_MIN, '', 'value']],
            [[null]],
            [[1]],
            [[-1]],
            [[0]],
            [[0.0]],
            [[1.7976931348623e+308]],
            [[2.2250738585072e-308]],
            [[\PHP_INT_MAX]],
            [[\PHP_INT_MIN]],
            [['']],
            [['value']],
            [[[[null, 1, -1, 0, 0.0, 1.7976931348623e+308, 2.2250738585072e-308, \PHP_INT_MAX, \PHP_INT_MIN, '', 'value']]]],
            [[[[null]]]],
            [[[[1]]]],
            [[[[-1]]]],
            [[[[0]]]],
            [[[[0.0]]]],
            [[[[1.7976931348623e+308]]]],
            [[[[2.2250738585072e-308]]]],
            [[[[\PHP_INT_MAX]]]],
            [[[[\PHP_INT_MIN]]]],
            [[[['']]]],
            [[[['value']]]],
        ];
    }

    public function invalidValues()
    {
        return [
            [new \stdClass()],
            [[new \stdClass()]],
            [[new \stdClass(), new \stdClass()]],
            [[null, 1, -1, 0, 0.0, 1.7976931348623e+308, 2.2250738585072e-308, \PHP_INT_MAX, \PHP_INT_MIN, '', 'value', new \stdClass()]],
            [[null, new \stdClass()]],
            [[1, new \stdClass()]],
            [[-1, new \stdClass()]],
            [[0, new \stdClass()]],
            [[0.0, new \stdClass()]],
            [[1.7976931348623e+308, new \stdClass()]],
            [[2.2250738585072e-308, new \stdClass()]],
            [[\PHP_INT_MAX, new \stdClass()]],
            [[\PHP_INT_MIN, new \stdClass()]],
            [['', new \stdClass()]],
            [['value', new \stdClass()]],
            [[[[null, 1, -1, 0, 0.0, 1.7976931348623e+308, 2.2250738585072e-308, \PHP_INT_MAX, \PHP_INT_MIN, '', 'value', new \stdClass()]]]],
            [[[[null, new \stdClass()]]]],
            [[[[1, new \stdClass()]]]],
            [[[[-1, new \stdClass()]]]],
            [[[[0, new \stdClass()]]]],
            [[[[0.0, new \stdClass()]]]],
            [[[[1.7976931348623e+308, new \stdClass()]]]],
            [[[[2.2250738585072e-308, new \stdClass()]]]],
            [[[[\PHP_INT_MAX, new \stdClass()]]]],
            [[[[\PHP_INT_MIN, new \stdClass()]]]],
            [[[['', new \stdClass()]]]],
            [[[['value', new \stdClass()]]]],
        ];
    }
}
