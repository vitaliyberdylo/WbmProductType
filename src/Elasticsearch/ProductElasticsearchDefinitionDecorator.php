<?php

declare(strict_types=1);

namespace WbmProductType\Elasticsearch;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use OpenSearchDSL\BuilderInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\IterableQuery;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Elasticsearch\Framework\AbstractElasticsearchDefinition;

class ProductElasticsearchDefinitionDecorator extends AbstractElasticsearchDefinition
{
    public function __construct(
        private readonly AbstractElasticsearchDefinition $inner,
        private readonly Connection $connection
    ) {
    }

    public function getEntityDefinition(): EntityDefinition
    {
        return $this->inner->getEntityDefinition();
    }

    public function getMapping(Context $context): array
    {
        $mapping = $this->inner->getMapping($context);
        $mapping['properties']['wbmExtension'] = [
            'type' => 'nested',
            'properties' => [
                'productType' => [
                    'type' => 'keyword',
                    'ignore_above' => 255,
                ],
            ],
        ];

        return $mapping;
    }

    public function getIterator(): ?IterableQuery
    {
        return $this->inner->getIterator();
    }

    public function fetch(array $ids, Context $context): array
    {
        $documents = $this->inner->fetch($ids, $context);

        if ($ids === []) {
            return $documents;
        }

        try {
            $versionId = Uuid::fromHexToBytes(Defaults::LIVE_VERSION);
            
            $rows = $this->connection->fetchAllAssociative(
                'SELECT 
                    LOWER(HEX(p.id)) AS product_id,
                    COALESCE(wpe.product_type, parent_wpe.product_type) AS product_type
                 FROM product p
                 LEFT JOIN wbm_product_extension wpe 
                     ON p.id = wpe.product_id 
                     AND wpe.product_version_id = :versionId
                     AND wpe.product_type IS NOT NULL
                     AND wpe.product_type != \'\'
                 LEFT JOIN product parent 
                     ON p.parent_id = parent.id 
                     AND parent.version_id = :versionId
                 LEFT JOIN wbm_product_extension parent_wpe 
                     ON parent.id = parent_wpe.product_id 
                     AND parent_wpe.product_version_id = :versionId
                     AND parent_wpe.product_type IS NOT NULL
                     AND parent_wpe.product_type != \'\'
                 WHERE p.id IN (:ids)
                   AND p.version_id = :versionId
                   AND (wpe.product_type IS NOT NULL OR parent_wpe.product_type IS NOT NULL)',
                [
                    'ids' => $ids,
                    'versionId' => $versionId,
                ],
                ['ids' => ArrayParameterType::STRING]
            );
        } catch (\Doctrine\DBAL\Exception $e) {
            return $documents;
        }

        $map = [];
        foreach ($rows as $row) {
            $map[$row['product_id']] = $row['product_type'];
        }

        foreach ($documents as $id => &$document) {
            $productType = $map[$id] ?? null;
            if ($productType !== null && $productType !== '') {
                $document['wbmExtension'] = ['productType' => $productType];
            }
        }

        return $documents;
    }

    public function buildTermQuery(Context $context, Criteria $criteria): BuilderInterface
    {
        return $this->inner->buildTermQuery($context, $criteria);
    }
}
