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

use SebastianBergmann\Comparator;
use Streak\Infrastructure\Domain\Event;

require_once __DIR__.'/../vendor/autoload.php';

$factory = Comparator\Factory::getInstance();
$factory->register(new Event\Envelope\Comparator());
