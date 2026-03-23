<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\Contract\OrderRepository;
use App\Domain\Order\Order;
use App\Domain\Order\OrderId;
use App\Infrastructure\Persistence\Doctrine\Entity\OrderRecord;
use App\Infrastructure\Persistence\Doctrine\Mapper\OrderRecordMapper;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineOrderRepository implements OrderRepository
{
    public function __construct(
        private EntityManagerInterface $em,
        private OrderRecordMapper $mapper,
    ) {
    }

    public function get(OrderId $id): ?Order
    {
        $record = $this->em->find(OrderRecord::class, $id->toString());

        if ($record === null) {
            return null;
        }

        return $this->mapper->toDomain($record);
    }

    public function save(Order $order): void
    {
        $existing = $this->em->find(OrderRecord::class, $order->id()->toString());
        $record = $this->mapper->toRecord($order, $existing);

        $this->em->persist($record);
    }
}
