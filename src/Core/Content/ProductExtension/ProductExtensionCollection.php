<?php

declare(strict_types=1);

namespace WbmProductType\Core\Content\ProductExtension;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class ProductExtensionCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ProductExtensionEntity::class;
    }
}
