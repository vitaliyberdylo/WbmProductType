<?php

declare(strict_types=1);

namespace WbmProductType\Core\Content\ProductExtension;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Symfony\Component\Validator\Constraints as Assert;

class ProductExtensionEntity extends Entity
{
    use EntityIdTrait;

    protected string $productId;

    protected string $productVersionId;

    #[Assert\PositiveOrZero]
    protected ?int $apiProductId = null;

    #[Assert\Length(['max' => 255])]
    protected ?string $productType = null;

    protected ?ProductEntity $product = null;

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getProductVersionId(): string
    {
        return $this->productVersionId;
    }

    public function setProductVersionId(string $productVersionId): void
    {
        $this->productVersionId = $productVersionId;
    }

    public function getApiProductId(): ?int
    {
        return $this->apiProductId;
    }

    public function setApiProductId(?int $apiProductId): void
    {
        $this->apiProductId = $apiProductId;
    }

    public function getProductType(): ?string
    {
        return $this->productType;
    }

    public function setProductType(?string $productType): void
    {
        $this->productType = $productType;
    }

    public function getProduct(): ?ProductEntity
    {
        return $this->product;
    }

    public function setProduct(?ProductEntity $product): void
    {
        $this->product = $product;
    }
}
