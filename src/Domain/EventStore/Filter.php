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

namespace Streak\Domain\EventStore;

use Streak\Domain\Id;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Domain\EventStore\FilterTest
 */
final class Filter
{
    /**
     * @var Id[]
     */
    private array $producerIds = [];

    /**
     * @var string[]
     */
    private array $producerTypes = [];

    public function __construct()
    {
    }

    public static function nothing() : Filter
    {
        return new self();
    }

    public function filterProducerIds(Id ...$ids) : Filter
    {
        $filter = new self();
        $filter->producerTypes = $this->producerTypes;
        $filter->producerIds = $this->producerIds;

        foreach ($ids as $id) {
            foreach ($this->producerIds as $producerId) {
                $r = $producerId->equals($id);
                if (true === $r) {
                    continue 2;
                }
            }
            $filter->producerIds[] = $id;
        }

        return $filter;
    }

    public function doNotFilterProducerIds() : Filter
    {
        $filter = new self();
        $filter->producerIds = [];
        $filter->producerTypes = $this->producerTypes;

        return $filter;
    }

    public function filterProducerTypes(string ...$types) : Filter
    {
        $filter = new self();
        $filter->producerIds = $this->producerIds;
        $filter->producerTypes = $this->producerTypes;

        foreach ($types as $type) {
            if (true === in_array($type, $filter->producerTypes, true)) {
                continue;
            }
            $filter->producerTypes[] = $type;
        }

        return $filter;
    }

    public function doNotFilterProducerTypes() : Filter
    {
        $filter = new self();
        $filter->producerIds = $this->producerIds;
        $filter->producerTypes = [];

        return $filter;
    }

    /**
     * @return Id[]
     */
    public function producerIds() : array
    {
        return $this->producerIds;
    }

    /**
     * @return string[]
     */
    public function producerTypes() : array
    {
        return $this->producerTypes;
    }
}
