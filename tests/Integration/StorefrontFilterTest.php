<?php

declare(strict_types=1);

namespace WbmProductType\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Test\Product\ProductBuilder;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Test\Stub\Framework\IdsCollection;

class StorefrontFilterTest extends TestCase
{
    use IntegrationTestBehaviour;

    private EntityRepository $productRepository;
    private Context $context;
    private IdsCollection $ids;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var EntityRepository $productRepository */
        $productRepository = static::getContainer()->get('product.repository');

        $this->productRepository = $productRepository;
        $this->context = Context::createDefaultContext();
        $this->ids = new IdsCollection();

        $this->createProducts();
    }

    public function testAggregationReturnsProductTypes(): void
    {
        $criteria = new Criteria();
        $criteria->addAssociation('wbmExtension');
        $criteria->addAggregation(new TermsAggregation('product_type', 'wbmExtension.productType'));

        $result = $this->productRepository->search($criteria, $this->context);
        $aggregation = $result->getAggregations()->get('product_type');

        static::assertNotNull($aggregation);

        $keys = array_map(static fn ($bucket) => $bucket->getKey(), $aggregation->getBuckets());
        static::assertContains('Bücher', $keys);
        static::assertContains('CD-ROMs', $keys);
    }

    public function testFilterByProductType(): void
    {
        $criteria = new Criteria();
        $criteria->addAssociation('wbmExtension');
        $criteria->addFilter(new EqualsFilter('wbmExtension.productType', 'Bücher'));

        $result = $this->productRepository->searchIds($criteria, $this->context);

        static::assertContains($this->ids->get('book'), $result->getIds());
        static::assertNotContains($this->ids->get('cd'), $result->getIds());
    }

    private function createProducts(): void
    {
        $book = (new ProductBuilder($this->ids, 'book'))
            ->price(10)
            ->visibility()
            ->build();
        $book['extensions'] = ['wbmExtension' => ['productType' => 'Bücher']];

        $cd = (new ProductBuilder($this->ids, 'cd'))
            ->price(15)
            ->visibility()
            ->build();
        $cd['extensions'] = ['wbmExtension' => ['productType' => 'CD-ROMs']];

        $this->productRepository->create([$book, $cd], $this->context);
    }
}
