<?php

declare(strict_types=1);

namespace WbmProductType\Elasticsearch\Admin;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\IterableQuery;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Elasticsearch\Admin\Indexer\AbstractAdminIndexer;
use WbmProductType\Core\Content\ProductExtension\ProductExtensionDefinition;

class ProductAdminSearchIndexerDecorator extends AbstractAdminIndexer
{
    public function __construct(
        private readonly AbstractAdminIndexer $inner,
        private readonly Connection $connection
    ) {
    }

    public function getDecorated(): AbstractAdminIndexer
    {
        return $this->inner;
    }

    public function getEntity(): string
    {
        return $this->inner->getEntity();
    }

    public function getName(): string
    {
        return $this->inner->getName();
    }

    public function getIterator(): IterableQuery
    {
        return $this->inner->getIterator();
    }

    public function globalData(array $result, Context $context): array
    {
        return $this->inner->globalData($result, $context);
    }

    public function getUpdatedIds(EntityWrittenContainerEvent $event): array
    {
        $ids = $this->inner->getUpdatedIds($event);

        $extensionIds = $event->getPrimaryKeysWithPropertyChange(
            ProductExtensionDefinition::ENTITY_NAME,
            ['productType']
        );

        if ($extensionIds === []) {
            return $ids;
        }

        try {
            $productIds = $this->connection->fetchFirstColumn(
                'SELECT LOWER(HEX(product_id)) FROM wbm_product_extension WHERE id IN (:ids)',
                ['ids' => Uuid::fromHexToBytesList($extensionIds)],
                ['ids' => ArrayParameterType::BINARY]
            );
        } catch (\Doctrine\DBAL\Exception) {
            return $ids;
        }

        return array_values(array_unique([...$ids, ...$productIds]));
    }

    public function fetch(array $ids): array
    {
        $documents = $this->inner->fetch($ids);

        if ($ids === []) {
            return $documents;
        }

        try {
            $rows = $this->connection->fetchAllAssociative(
                'SELECT LOWER(HEX(product_id)) AS product_id, product_type
                 FROM wbm_product_extension
                 WHERE product_id IN (:ids)
                   AND product_version_id = :versionId',
                [
                    'ids' => Uuid::fromHexToBytesList($ids),
                    'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                ],
                ['ids' => ArrayParameterType::BINARY]
            );
        } catch (\Doctrine\DBAL\Exception) {
            return $documents;
        }

        $map = [];
        foreach ($rows as $row) {
            $map[$row['product_id']] = $row['product_type'];
        }

        foreach ($documents as $id => &$document) {
            $productType = $map[$id] ?? null;

            if ($productType !== null) {
                $document['text'] = ($document['text'] ?? '') . ' ' . strtolower($productType);
                $document['textBoosted'] = ($document['textBoosted'] ?? '') . ' ' . strtolower($productType);
            }
        }

        return $documents;
    }
}
