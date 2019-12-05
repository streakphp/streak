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

namespace Streak\Infrastructure\Event\Sourced\Subscription;

use PHPUnit\Framework\TestCase;
use Streak\Domain\Event\Listener\State;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Event\Sourced\Subscription\InMemoryState
 */
class InMemoryStateTest extends TestCase
{
    /**
     * Stub acting as ['attribute-1' => 'value-1'] state.
     *
     * @var State
     */
    private $stub;

    protected function setUp()
    {
        $this->stub = new class() implements State {
            public function equals($object) : bool
            {
                throw new \BadMethodCallException('Do not call method State::equals() on this stub.');
            }

            public function has(string $name) : bool
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

            public function toArray() : array
            {
                return ['attribute-1' => 'value-1'];
            }

            public function set(string $name, $value) : State
            {
                throw new \BadMethodCallException('Do not call method State::set() on this stub.');
            }
        };
    }

    public function testState()
    {
        $state1a = InMemoryState::empty();
        $state2a = InMemoryState::empty();

        $this->assertFalse($state1a->equals(null));
        $this->assertFalse($state2a->equals(null));
        $this->assertFalse($state1a->equals(new \stdClass()));
        $this->assertFalse($state2a->equals(new \stdClass()));

        $this->assertFalse($state1a->equals($this->stub));
        $this->assertFalse($state2a->equals($this->stub));

        $this->assertTrue($state1a->equals($state1a));
        $this->assertTrue($state2a->equals($state2a));
        $this->assertTrue($state1a->equals($state2a));
        $this->assertTrue($state2a->equals($state1a));

        $this->assertSame([], $state1a->toArray());
        $this->assertSame([], $state2a->toArray());

        $this->assertFalse($state1a->has('attribute-1'));
        $this->assertFalse($state2a->has('attribute-1'));
        $this->assertFalse($state1a->has('attribute-2'));
        $this->assertFalse($state2a->has('attribute-2'));

        $state1b = $state1a->set('attribute-1', 'value-1');

        $this->assertTrue($state1b->equals($this->stub)); // although states are both of different types they contain same data.

        $this->assertNotSame($state1a, $state1b);
        $this->assertFalse($state1a->has('attribute-1'));
        $this->assertSame([], $state1a->toArray());
        $this->assertTrue($state1b->has('attribute-1'));
        $this->assertFalse($state1b->has('attribute-2'));
        $this->assertSame(['attribute-1' => 'value-1'], $state1b->toArray());
        $this->assertSame('value-1', $state1b->get('attribute-1'));

        $state1c = $state1a->set('attribute-1', 'value-1');

        $this->assertTrue($state1c->equals($this->stub)); // although states are both of different types they contain same data.

        $this->assertNotSame($state1a, $state1c);
        $this->assertFalse($state1a->has('attribute-1'));
        $this->assertSame([], $state1a->toArray());
        $this->assertTrue($state1c->has('attribute-1'));
        $this->assertFalse($state1c->has('attribute-2'));
        $this->assertSame(['attribute-1' => 'value-1'], $state1c->toArray());
        $this->assertSame('value-1', $state1c->get('attribute-1'));

        $state1d = $state1c->set('attribute-2', 'value-2');

        $this->assertFalse($state1d->equals($this->stub)); // although states are both of different types they contain same data.

        $this->assertNotSame($state1c, $state1d);
        $this->assertTrue($state1c->has('attribute-1'));
        $this->assertTrue($state1c->has('attribute-1'));
        $this->assertSame(['attribute-1' => 'value-1'], $state1c->toArray());
        $this->assertTrue($state1d->has('attribute-1'));
        $this->assertTrue($state1d->has('attribute-2'));
        $this->assertSame(['attribute-1' => 'value-1', 'attribute-2' => 'value-2'], $state1d->toArray());
        $this->assertSame('value-1', $state1d->get('attribute-1'));
        $this->assertSame('value-2', $state1d->get('attribute-2'));

        $state1e = $state1d->set('attribute-2', 'value-3');

        $this->assertFalse($state1d->equals($this->stub)); // although states are both of different types they contain same data.

        $this->assertNotSame($state1c, $state1d);
        $this->assertFalse($state1e->equals($state1d));

        $state3a = InMemoryState::fromArray(['attribute-1' => 'value-1', 'attribute-2' => 'value-2']);

        $this->assertTrue($state3a->has('attribute-1'));
        $this->assertTrue($state3a->has('attribute-2'));
        $this->assertSame(['attribute-1' => 'value-1', 'attribute-2' => 'value-2'], $state3a->toArray());
        $this->assertSame('value-1', $state3a->get('attribute-1'));
        $this->assertSame('value-2', $state3a->get('attribute-2'));

        $this->assertTrue($state3a->equals($state1d));
        $this->assertTrue($state1d->equals($state3a));

        $this->assertFalse($state3a->equals($state1a));
        $this->assertFalse($state3a->equals($state1b));
        $this->assertFalse($state3a->equals($state1c));
        $this->assertFalse($state3a->equals($state2a));
        $this->assertFalse($state1a->equals($state3a));
        $this->assertFalse($state1b->equals($state3a));
        $this->assertFalse($state1c->equals($state3a));
        $this->assertFalse($state2a->equals($state3a));

        $state4a = InMemoryState::fromState($state3a);

        $this->assertTrue($state4a->equals($state3a));
        $this->assertTrue($state3a->equals($state4a));

        $this->assertTrue($state3a->equals($state1d));
        $this->assertTrue($state1d->equals($state3a));

        $this->assertFalse($state3a->equals($state1a));
        $this->assertFalse($state3a->equals($state1b));
        $this->assertFalse($state3a->equals($state1c));
        $this->assertFalse($state3a->equals($state2a));
        $this->assertFalse($state1a->equals($state3a));
        $this->assertFalse($state1b->equals($state3a));
        $this->assertFalse($state1c->equals($state3a));
        $this->assertFalse($state2a->equals($state3a));
    }

    public function testGettingMissingName()
    {
        $this->expectException(\OutOfBoundsException::class);

        $state = InMemoryState::empty();
        $state->get('missing-name');
    }

    public function testSettingInvalidName()
    {
        $this->expectException(\OutOfBoundsException::class);

        $state = InMemoryState::empty();
        $state->set('', 'value-1');
    }

    /**
     * @dataProvider validValues
     */
    public function testSettingValidValues($value)
    {
        $state = InMemoryState::empty();
        $result = $state->set('name', $value);

        $this->assertInstanceOf(InMemoryState::class, $result);
        $this->assertNotEquals($state, $result);
        $this->assertNotSame($state, $result);

        $this->assertSame($value, $result->get('name'));
    }

    /**
     * @dataProvider invalidValues
     */
    public function testSettingInvalidValue($value)
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
            [PHP_FLOAT_MAX],
            [PHP_FLOAT_MIN],
            [PHP_INT_MAX],
            [PHP_INT_MIN],
            [''],
            ['value'],
            [[null, 1, -1, 0, 0.0, PHP_FLOAT_MAX, PHP_FLOAT_MIN, PHP_INT_MAX, PHP_INT_MIN, '', 'value']],
            [[null]],
            [[1]],
            [[-1]],
            [[0]],
            [[0.0]],
            [[PHP_FLOAT_MAX]],
            [[PHP_FLOAT_MIN]],
            [[PHP_INT_MAX]],
            [[PHP_INT_MIN]],
            [['']],
            [['value']],
            [[[[null, 1, -1, 0, 0.0, PHP_FLOAT_MAX, PHP_FLOAT_MIN, PHP_INT_MAX, PHP_INT_MIN, '', 'value']]]],
            [[[[null]]]],
            [[[[1]]]],
            [[[[-1]]]],
            [[[[0]]]],
            [[[[0.0]]]],
            [[[[PHP_FLOAT_MAX]]]],
            [[[[PHP_FLOAT_MIN]]]],
            [[[[PHP_INT_MAX]]]],
            [[[[PHP_INT_MIN]]]],
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
            [[null, 1, -1, 0, 0.0, PHP_FLOAT_MAX, PHP_FLOAT_MIN, PHP_INT_MAX, PHP_INT_MIN, '', 'value', new \stdClass()]],
            [[null, new \stdClass()]],
            [[1, new \stdClass()]],
            [[-1, new \stdClass()]],
            [[0, new \stdClass()]],
            [[0.0, new \stdClass()]],
            [[PHP_FLOAT_MAX, new \stdClass()]],
            [[PHP_FLOAT_MIN, new \stdClass()]],
            [[PHP_INT_MAX, new \stdClass()]],
            [[PHP_INT_MIN, new \stdClass()]],
            [['', new \stdClass()]],
            [['value', new \stdClass()]],
            [[[[null, 1, -1, 0, 0.0, PHP_FLOAT_MAX, PHP_FLOAT_MIN, PHP_INT_MAX, PHP_INT_MIN, '', 'value', new \stdClass()]]]],
            [[[[null, new \stdClass()]]]],
            [[[[1, new \stdClass()]]]],
            [[[[-1, new \stdClass()]]]],
            [[[[0, new \stdClass()]]]],
            [[[[0.0, new \stdClass()]]]],
            [[[[PHP_FLOAT_MAX, new \stdClass()]]]],
            [[[[PHP_FLOAT_MIN, new \stdClass()]]]],
            [[[[PHP_INT_MAX, new \stdClass()]]]],
            [[[[PHP_INT_MIN, new \stdClass()]]]],
            [[[['', new \stdClass()]]]],
            [[[['value', new \stdClass()]]]],
        ];
    }
}
