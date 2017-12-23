<?php

declare(strict_types=1);

/**
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain;

use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * Unfortunately covariant return types are not supported yet.
 *
 * @see https://wiki.php.net/rfc/return_types#variance_and_signature_validation
 */
interface AggregateRoot extends Domain\Aggregate
{
    public function aggregateRootId() : AggregateRoot\Id;
}
