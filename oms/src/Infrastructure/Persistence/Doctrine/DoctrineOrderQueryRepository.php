<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\Contract\OrderQueryRepository;
use App\Application\DTO\OrderListQuery;
use App\Application\DTO\OrderListResult;
use App\Application\DTO\OrderView;
use App\Domain\Order\OrderId;
use App\Infrastructure\Persistence\Doctrine\Entity\OrderRecord;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineOrderQueryRepository implements OrderQueryRepository
{
    public function __construct(
        private EntityManagerInterface $em,
        private OrderIndexQuery $indexQuery,
    )
    {
    }

    public function get(OrderId $id): ?OrderView
    {
        $record = $this->em->find(OrderRecord::class, $id->toString());

        if ($record === null) {
            return null;
        }

        return $this->toView($record);
    }

    public function list(OrderListQuery $query): OrderListResult
    {
        $page = $this->indexQuery->page($query);
        $perPage = $this->indexQuery->perPage($query);

        $qb = $this->indexQuery->build($query);
        $qb->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        $items = array_map(
            fn (OrderRecord $record): OrderView => $this->toView($record),
            $qb->getQuery()->getResult(),
        );

        $countQb = $this->indexQuery->build($query);
        $countQb->resetDQLPart('orderBy');
        $countQb->select('COUNT(o.id)');

        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        return new OrderListResult(
            items: $items,
            page: $page,
            perPage: $perPage,
            total: $total,
        );
    }

    private function toView(OrderRecord $record): OrderView
    {
        return new OrderView(
            id: $record->id(),
            status: $record->status(),
            amountMinor: $record->amountMinor(),
            currency: $record->currency(),
            createdAt: $record->createdAt()->format(DATE_RFC3339),
            updatedAt: $record->updatedAt()->format(DATE_RFC3339),
        );
    }
}
