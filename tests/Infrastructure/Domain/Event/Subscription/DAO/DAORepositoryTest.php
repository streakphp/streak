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

namespace Streak\Infrastructure\Domain\Event\Subscription\DAO;

use PHPUnit\Framework\TestCase;
use Streak\Domain\Event\Listener\Id;
use Streak\Domain\Event\Subscription;
use Streak\Domain\Event\Subscription\Repository\Filter;
use Streak\Infrastructure\Domain\Event\Subscription\DAO;
use Streak\Infrastructure\Domain\UnitOfWork;

/**
 * @covers \Streak\Infrastructure\Domain\Event\Subscription\DAO\DAORepository
 */
class DAORepositoryTest extends TestCase
{
    private DAORepository $daoRepository;

    private DAO $dao;

    private UnitOfWork $uow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao = $this->getMockBuilder(DAO::class)->getMockForAbstractClass();
        $this->uow = $this->getMockBuilder(UnitOfWork::class)->getMockForAbstractClass();
        $this->daoRepository = new DAORepository($this->dao, $this->uow);
    }

    public function testItFinds(): void
    {
        $subscription = $this->createSubscriptionMock();
        $this->dao->expects(self::once())->method('one')->willReturn($subscription);
        $this->uow->expects(self::once())->method('add')->with($subscription);
        self::assertSame($subscription, $this->daoRepository->find($this->createIdMock()));
    }

    public function testItFindsNoting(): void
    {
        $this->dao->expects(self::once())->method('one')->willReturn(null);
        $this->uow->expects(self::never())->method('add');
        self::assertNull($this->daoRepository->find($this->createIdMock()));
    }

    public function testItHas(): void
    {
        $this->dao->expects(self::at(0))->method('exists')->willReturn(true);
        $this->dao->expects(self::at(1))->method('exists')->willReturn(false);
        self::assertTrue($this->daoRepository->has($this->createSubscriptionMock()));
        self::assertFalse($this->daoRepository->has($this->createSubscriptionMock()));
    }

    public function testItAdds(): void
    {
        $subscription = $this->createSubscriptionMock();
        $this->uow->expects(self::once())->method('add')->with($subscription);
        $this->daoRepository->add($subscription);
    }

    /** @dataProvider filtersProvider */
    public function testItAll(?Filter $filter, $daoAllSecondArgument): void
    {
        $expectedSubscriptionTypes = [];
        if ($filter) {
            $expectedSubscriptionTypes = $filter->subscriptionTypes();
        }
        $subscription = $this->createSubscriptionMock();
        $this->dao->expects(self::at(0))->method('all')->willReturnCallback(
            function () use ($subscription): iterable {
                yield $subscription;
            }
        )->with($expectedSubscriptionTypes, $daoAllSecondArgument);
        $this->uow->expects(self::once())->method('add')->with($subscription);

        $this->dao->expects(self::at(1))->method('all')->willReturnCallback(
            function (): iterable {
                yield from [];
            }
        );
        self::assertEquals([$subscription], iterator_to_array($this->daoRepository->all($filter)));
        self::assertEquals([], iterator_to_array($this->daoRepository->all($filter)));
    }

    public function filtersProvider()
    {
        return [
            [
                (new Filter())->filterSubscriptionTypes('Type!', 'Type2')->ignoreCompletedSubscriptions(),
                false,
            ],
            [
                (new Filter())->filterSubscriptionTypes('Type!', 'Type2'),
                null,
            ],
            [
                null,
                null,
            ],
        ];
    }

    private function createSubscriptionMock(): Subscription
    {
        return $this->getMockBuilder(Subscription::class)->getMockForAbstractClass();
    }

    private function createIdMock(): Id
    {
        return $this->getMockBuilder(Id::class)->getMockForAbstractClass();
    }
}
