<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Streak\Infrastructure\Persistable;

use PHPUnit\Framework\TestCase;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Persistable\InMemoryState
 */
class InMemoryStateTest extends TestCase
{
    public function testState()
    {
        $state = new InMemoryState();

        $this->assertNull($state->get('name-1'));
        $this->assertNull($state->get('name-2'));
        $this->assertEquals('default-1', $state->get('name-1', 'default-1'));
        $this->assertEquals('default-2', $state->get('name-2', 'default-2'));

        $state->set('name-1', 'value-1a');

        $this->assertEquals('value-1a', $state->get('name-1'));
        $this->assertNull($state->get('name-2'));
        $this->assertEquals('value-1a', $state->get('name-1', 'default-1'));
        $this->assertEquals('default-2', $state->get('name-2', 'default-2'));

        $state->set('name-1', 'value-1b');

        $this->assertEquals('value-1b', $state->get('name-1'));
        $this->assertNull($state->get('name-2'));
        $this->assertEquals('value-1b', $state->get('name-1', 'default-1'));
        $this->assertEquals('default-2', $state->get('name-2', 'default-2'));
    }
}
