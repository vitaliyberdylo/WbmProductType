<?php

declare(strict_types=1);

namespace WbmProductType\Core\Content\ProductExtension;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ProductExtensionDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'wbm_product_extension';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return ProductExtensionEntity::class;
    }

    public function getCollectionClass(): string
    {
        return ProductExtensionCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required(), new ApiAware()),
            (new FkField('product_id', 'productId', ProductDefinition::class))
                ->addFlags(new Required(), new ApiAware()),
            (new ReferenceVersionField(ProductDefinition::class, 'product_version_id'))
                ->addFlags(new Required(), new ApiAware()),
            (new IntField('api_product_id', 'apiProductId'))->addFlags(new ApiAware()),
            (new StringField('product_type', 'productType'))->addFlags(new ApiAware()),
            new OneToOneAssociationField('product', 'product_id', 'id', ProductDefinition::class, false),
            new CreatedAtField(),
            new UpdatedAtField(),
        ]);
    }
}
