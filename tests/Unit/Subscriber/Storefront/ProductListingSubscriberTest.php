<?php

declare(strict_types=1);

namespace WbmProductType\Tests\Unit\Subscriber\Storefront;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\Events\ProductListingCriteriaEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Elasticsearch\Framework\ElasticsearchHelper;
use Symfony\Component\HttpFoundation\Request;
use WbmProductType\Helper\ProductTypeFilterHelper;
use WbmProductType\Subscriber\Storefront\ProductListingSubscriber;

#[CoversClass(ProductListingSubscriber::class)]
#[AllowMockObjectsWithoutExpectations]
class ProductListingSubscriberTest extends TestCase
{
    private ProductTypeFilterHelper&MockObject $filterHelper;
    private ElasticsearchHelper&MockObject $elasticsearchHelper;
    private ProductListingSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->filterHelper = $this->createMock(ProductTypeFilterHelper::class);
        $this->elasticsearchHelper = $this->createMock(ElasticsearchHelper::class);
        $this->elasticsearchHelper->method('allowIndexing')->willReturn(true);
        $this->subscriber = new ProductListingSubscriber($this->filterHelper, $this->elasticsearchHelper);
    }
    public function testSubscribedEvents(): void
    {
        static::assertArrayHasKey(
            ProductListingCriteriaEvent::class,
            ProductListingSubscriber::getSubscribedEvents()
        );
    }

    public function testAddsAssociationAndAggregation(): void
    {
        $this->filterHelper->expects(static::once())
            ->method('parseFilterValues')
            ->with(null)
            ->willReturn([]);
        
        $event = $this->createEvent();

        $this->subscriber->onCriteria($event);

        $criteria = $event->getCriteria();

        static::assertTrue($criteria->hasAssociation('wbmExtension'));

        $aggregation = $criteria->getAggregation('product_type');
        static::assertInstanceOf(TermsAggregation::class, $aggregation);
        static::assertSame('wbmExtension.productType', $aggregation->getField());
    }

    public function testAddsFilterWithParsedValues(): void
    {
        $this->filterHelper->expects(static::once())
            ->method('parseFilterValues')
            ->with('books|cds')
            ->willReturn(['books', 'cds']);
        
        $event = $this->createEvent(['productType' => 'books|cds']);

        $this->subscriber->onCriteria($event);

        $filters = $event->getCriteria()->getFilters();
        static::assertCount(1, $filters);

        $filter = reset($filters);
        static::assertInstanceOf(EqualsAnyFilter::class, $filter);
        static::assertSame('wbmExtension.productType', $filter->getField());
        static::assertSame(['books', 'cds'], $filter->getValue());
    }

    public function testAddsFilterFromArray(): void
    {
        $this->filterHelper->expects(static::once())
            ->method('parseFilterValues')
            ->with(['books', 'cds'])
            ->willReturn(['books', 'cds']);
        
        $request = Request::create('/', 'GET');
        $request->query->set('productType', ['books', 'cds']);

        $event = new ProductListingCriteriaEvent(
            $request,
            new Criteria(),
            $this->createMock(SalesChannelContext::class)
        );

        $this->subscriber->onCriteria($event);

        $filters = $event->getCriteria()->getFilters();
        static::assertCount(1, $filters);

        $filter = reset($filters);
        static::assertSame(['books', 'cds'], $filter->getValue());
    }

    public function testNoFilterWhenProductTypeAbsent(): void
    {
        $this->filterHelper->expects(static::once())
            ->method('parseFilterValues')
            ->with(null)
            ->willReturn([]);
        
        $event = $this->createEvent();

        $this->subscriber->onCriteria($event);

        static::assertEmpty($event->getCriteria()->getFilters());
    }

    #[DataProvider('emptyFilterProvider')]
    public function testNoFilterForEmptyValues(mixed $value): void
    {
        $this->filterHelper->expects(static::once())
            ->method('parseFilterValues')
            ->with($value)
            ->willReturn([]);
        
        $event = $this->createEvent(['productType' => $value]);

        $this->subscriber->onCriteria($event);

        static::assertEmpty($event->getCriteria()->getFilters());
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function emptyFilterProvider(): iterable
    {
        yield 'empty string' => [''];
        yield 'whitespace only' => ['   '];
        yield 'pipe only' => ['|'];
        yield 'pipes and spaces' => ['  |  |  '];
    }

    public function testTrimsAndDeduplicatesFilterValues(): void
    {
        $this->filterHelper->expects(static::once())
            ->method('parseFilterValues')
            ->with('  books  | books |  cds  ')
            ->willReturn(['books', 'cds']);
        
        $event = $this->createEvent(['productType' => '  books  | books |  cds  ']);

        $this->subscriber->onCriteria($event);

        $filters = $event->getCriteria()->getFilters();
        static::assertCount(1, $filters);
        static::assertSame(['books', 'cds'], $filters[0]->getValue());
    }

    public function testElasticsearchDisabled(): void
    {
        $elasticsearchHelper = $this->createMock(ElasticsearchHelper::class);
        $elasticsearchHelper->method('allowIndexing')->willReturn(false);
        $subscriber = new ProductListingSubscriber($this->filterHelper, $elasticsearchHelper);

        $event = $this->createEvent(['search' => 'books', 'productType' => 'cds']);

        $this->filterHelper->expects(static::never())
            ->method('parseFilterValues');

        $subscriber->onCriteria($event);

        $criteria = $event->getCriteria();

        static::assertFalse($criteria->hasAssociation('wbmExtension'));
        static::assertNull($criteria->getAggregation('product_type'));
        static::assertEmpty($criteria->getFilters());
        static::assertEmpty($criteria->getQueries());
    }

    /**
     * @param array<string, mixed> $query
     */
    private function createEvent(array $query = []): ProductListingCriteriaEvent
    {
        return new ProductListingCriteriaEvent(
            new Request($query),
            new Criteria(),
            $this->createMock(SalesChannelContext::class)
        );
    }
}
