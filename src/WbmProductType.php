<?php

declare(strict_types=1);

namespace WbmProductType;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class WbmProductType extends Plugin
{
    public function install(InstallContext $context): void
    {
        $esEnabled = $this->container->getParameter('elasticsearch.enabled');
        if (!$esEnabled) {
            throw new \RuntimeException('Elasticsearch is required for this plugin. Please set SHOPWARE_ES_ENABLED=1 in your .env file.');
        }

        $esIndexingEnabled = $this->container->getParameter('elasticsearch.indexing_enabled');
        if (!$esIndexingEnabled) {
            throw new \RuntimeException('Elasticsearch indexing is required for this plugin. Please set SHOPWARE_ES_INDEXING_ENABLED=1 in your .env file.');
        }

        parent::install($context);
    }

    public function uninstall(UninstallContext $context): void
    {
        if ($context->keepUserData()) {
            return;
        }

        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);
        $connection->executeStatement('DROP TABLE IF EXISTS `wbm_product_extension`');
    }
}
