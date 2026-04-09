<?php

declare(strict_types=1);

namespace WbmProductType\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1712000000AddProductTypeToSearchConfig extends MigrationStep
{
    private const FIELD = 'wbmExtension.productType';
    private const RANKING = 500;

    public function getCreationTimestamp(): int
    {
        return 1712000000;
    }

    public function update(Connection $connection): void
    {
        try {
            $configIds = $connection->fetchFirstColumn('SELECT id FROM product_search_config');

            foreach ($configIds as $configId) {
                $exists = $connection->fetchOne(
                    'SELECT 1 FROM product_search_config_field
                         WHERE product_search_config_id = ? AND field = ?',
                    [$configId, self::FIELD]
                );

                if ($exists) {
                    continue;
                }

                $connection->insert('product_search_config_field', [
                    'id' => Uuid::randomBytes(),
                    'product_search_config_id' => $configId,
                    'field' => self::FIELD,
                    'tokenize' => 0,
                    'searchable' => 1,
                    'ranking' => self::RANKING,
                    'created_at' => (new \DateTime())->format('Y-m-d H:i:s.v'),
                ]);
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            throw new \RuntimeException('Migration failed: Unable to update search configuration', 0, $e);
        }
    }
}
