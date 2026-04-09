<?php

declare(strict_types=1);

namespace WbmProductType\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Test\Product\ProductBuilder;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Test\Stub\Framework\IdsCollection;
use WbmProductType\Core\Content\ProductExtension\ProductExtensionEntity;

class ProductExtensionTest extends TestCase
{
    use IntegrationTestBehaviour;

    private EntityRepository $productRepository;
    private EntityRepository $extensionRepository;
    private Context $context;
    private IdsCollection $ids;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var EntityRepository $productRepository */
        $productRepository = static::getContainer()->get('product.repository');

        /** @var EntityRepository $extensionRepository */
        $extensionRepository = static::getContainer()->get('wbm_product_extension.repository');

        $this->productRepository = $productRepository;
        $this->extensionRepository = $extensionRepository;
        $this->context = Context::createDefaultContext();
        $this->ids = new IdsCollection();
    }

    public function testCreateProductWithExtension(): void
    {
        $this->createProductWithExtension('test-product', 'Bücher', 42);

        $criteria = (new Criteria([$this->ids->get('test-product')]))
            ->addAssociation('wbmExtension');

        /** @var ProductEntity $product */
        $product = $this->productRepository->search($criteria, $this->context)->first();
        static::assertNotNull($product);

        $extension = $product->getExtension('wbmExtension');

        static::assertInstanceOf(ProductExtensionEntity::class, $extension);
        static::assertSame('Bücher', $extension->getProductType());
        static::assertSame(42, $extension->getApiProductId());
    }

    public function testUpdateProductExtension(): void
    {
        $this->createProductWithExtension('update-product', 'CD-ROMs');

        $criteria = (new Criteria([$this->ids->get('update-product')]))
            ->addAssociation('wbmExtension');

        /** @var ProductEntity $product */
        $product = $this->productRepository->search($criteria, $this->context)->first();
        $extensionId = $product->getExtension('wbmExtension')->getId();

        $this->productRepository->update([[
            'id' => $this->ids->get('update-product'),
            'extensions' => ['wbmExtension' => ['id' => $extensionId, 'productType' => 'DVD-ROMs']],
        ]], $this->context);

        /** @var ProductEntity $product */
        $product = $this->productRepository->search($criteria, $this->context)->first();
        static::assertSame('DVD-ROMs', $product->getExtension('wbmExtension')->getProductType());
    }

    public function testProductDeletionCascadesToExtension(): void
    {
        $this->createProductWithExtension('cascade-product', 'Zeitschriften');

        $criteria = (new Criteria())->addFilter(new EqualsFilter('productId', $this->ids->get('cascade-product')));
        static::assertSame(1, $this->extensionRepository->search($criteria, $this->context)->getTotal());

        $this->productRepository->delete([['id' => $this->ids->get('cascade-product')]], $this->context);

        static::assertSame(0, $this->extensionRepository->search($criteria, $this->context)->getTotal());
    }

    public function testFilterProductsByExtensionField(): void
    {
        $this->createProductWithExtension('book-1', 'Bücher', price: 10);
        $this->createProductWithExtension('cd-1', 'CD-ROMs', price: 15);
        $this->createProductWithExtension('book-2', 'Bücher', price: 20);

        $criteria = (new Criteria())->addFilter(new EqualsFilter('wbmExtension.productType', 'Bücher'));
        $result = $this->productRepository->searchIds($criteria, $this->context);

        static::assertContains($this->ids->get('book-1'), $result->getIds());
        static::assertContains($this->ids->get('book-2'), $result->getIds());
        static::assertNotContains($this->ids->get('cd-1'), $result->getIds());
    }

    public function testExtensionLoadedViaAssociation(): void
    {
        $this->createProductWithExtension('assoc-product', 'Test', 42);

        $criteria = (new Criteria([$this->ids->get('assoc-product')]))->addAssociation('wbmExtension');
        $product = $this->productRepository->search($criteria, $this->context)->first();

        $extension = $product->getExtension('wbmExtension');

        static::assertNotNull($extension);
        static::assertSame('Test', $extension->getProductType());
        static::assertSame(42, $extension->getApiProductId());
    }

    private function createProductWithExtension(
        string $key,
        string $productType,
        ?int $apiProductId = null,
        float $price = 19.99
    ): void {
        $product = (new ProductBuilder($this->ids, $key))->price($price)->build();
        $product['extensions'] = [
            'wbmExtension' => array_filter([
                'productType' => $productType,
                'apiProductId' => $apiProductId,
            ], static fn ($v) => $v !== null),
        ];

        $this->productRepository->create([$product], $this->context);
    }
}
