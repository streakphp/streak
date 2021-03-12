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

namespace Streak\Domain\Event\Subscription\Criteria;

use Streak\Domain\Event\Subscription\Criteria;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class AndCriteria implements Criteria
{
    private $criteria;

    public function __construct(Criteria... $criteria)
    {
        $this->criteria = $criteria;
    }

    public function accept(Visitor $visitor)
    {
        foreach ($this->criteria as $criterion) {
            $criterion->accept($visitor);
        }

        $visitor->visitAnd($this);
    }
}
