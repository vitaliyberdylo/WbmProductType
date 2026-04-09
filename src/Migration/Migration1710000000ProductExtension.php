<?php

declare(strict_types=1);

namespace WbmProductType\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1710000000ProductExtension extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1710000000;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(<<<SQL
CREATE TABLE IF NOT EXISTS `wbm_product_extension` (
    `id` BINARY(16) NOT NULL,
    `product_id` BINARY(16) NOT NULL,
    `product_version_id` BINARY(16) NOT NULL,
    `api_product_id` INT NULL,
    `product_type` VARCHAR(255) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq.wbm_product_extension.product` (`product_id`, `product_version_id`),
    KEY `idx.wbm_product_extension.product_type` (`product_type`),
    CONSTRAINT `fk.wbm_product_extension.product`
        FOREIGN KEY (`product_id`, `product_version_id`)
        REFERENCES `product` (`id`, `version_id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

            }
}
