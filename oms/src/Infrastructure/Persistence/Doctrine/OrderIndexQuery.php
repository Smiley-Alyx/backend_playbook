<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\DTO\OrderListQuery;
use App\Infrastructure\Persistence\Doctrine\Entity\OrderRecord;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

final readonly class OrderIndexQuery
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function page(OrderListQuery $query): int
    {
        return max(1, $query->page);
    }

    public function perPage(OrderListQuery $query): int
    {
        return max(1, min(100, $query->perPage));
    }

    public function build(OrderListQuery $query): QueryBuilder
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('o')
            ->from(OrderRecord::class, 'o')
            ->orderBy('o.createdAt', 'DESC');

        if ($query->status !== null) {
            $qb->andWhere('o.status = :status')->setParameter('status', $query->status);
        }

        return $qb;
    }
}
