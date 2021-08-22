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

namespace Streak\Domain;

use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * Unfortunately covariant return types are not supported yet.
 *
 * @see https://wiki.php.net/rfc/return_types#variance_and_signature_validation
 */
interface Aggregate extends Domain\Entity
{
    public function id(): Domain\Aggregate\Id;
}
