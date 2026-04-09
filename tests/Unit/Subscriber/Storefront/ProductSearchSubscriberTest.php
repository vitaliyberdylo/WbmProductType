<?php

declare(strict_types=1);

namespace WbmProductType\Tests\Unit\Subscriber\Storefront;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\Events\ProductSearchCriteriaEvent;
use Shopware\Core\Content\Product\Events\ProductSuggestCriteriaEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use WbmProductType\Helper\ProductTypeFilterHelper;
use WbmProductType\Subscriber\Storefront\ProductSearchSubscriber;

#[CoversClass(ProductSearchSubscriber::class)]
#[AllowMockObjectsWithoutExpectations]
class ProductSearchSubscriberTest extends TestCase
{
    private ProductTypeFilterHelper&MockObject $filterHelper;
    private ProductSearchSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->filterHelper = $this->createMock(ProductTypeFilterHelper::class);
        $this->subscriber = new ProductSearchSubscriber($this->filterHelper);
    }
    public function testSubscribedEvents(): void
    {
        $events = ProductSearchSubscriber::getSubscribedEvents();
        static::assertArrayHasKey(ProductSearchCriteriaEvent::class, $events);
        static::assertArrayHasKey(ProductSuggestCriteriaEvent::class, $events);
    }

    public function testAddsAssociationAndAggregation(): void
    {
        $this->filterHelper->expects(static::once())
            ->method('parseFilterValues')
            ->with(null)
            ->willReturn([]);

        $event = $this->createEvent(['search' => 'something']);

        $this->subscriber->onCriteria($event);

        $criteria = $event->getCriteria();

        static::assertTrue($criteria->hasAssociation('wbmExtension'));
        static::assertNotNull($criteria->getAggregation('product_type'));
    }

    public function testAddsFilterWithParsedValues(): void
    {
        $this->filterHelper->expects(static::once())
            ->method('parseFilterValues')
            ->with('books|cds')
            ->willReturn(['books', 'cds']);

        $event = $this->createEvent(['search' => 'test', 'productType' => 'books|cds']);

        $this->subscriber->onCriteria($event);

        $filters = $event->getCriteria()->getFilters();
        static::assertCount(1, $filters);
        static::assertInstanceOf(EqualsAnyFilter::class, $filters[0]);
        static::assertSame('wbmExtension.productType', $filters[0]->getField());
        static::assertSame(['books', 'cds'], $filters[0]->getValue());
    }

    public function testAddsScoreQueryMatchingSearchTerm(): void
    {
        $this->filterHelper->expects(static::once())
            ->method('parseFilterValues')
            ->with(null)
            ->willReturn([]);

        $event = $this->createEvent(['search' => 'books']);

        $this->subscriber->onCriteria($event);

        $queries = $event->getCriteria()->getQueries();
        static::assertCount(1, $queries);
        static::assertSame(500, (int) $queries[0]->getScore());

        $filter = $queries[0]->getQuery();
        static::assertInstanceOf(EqualsFilter::class, $filter);
        static::assertSame('wbmExtension.productType', $filter->getField());
        static::assertSame('books', $filter->getValue());
    }

    #[DataProvider('searchTermProvider')]
    public function testNoScoreQueryWhenSearchTermEmptyOrWhitespace(string $searchTerm): void
    {
        $this->filterHelper->expects(static::once())
            ->method('parseFilterValues')
            ->with(null)
            ->willReturn([]);

        $event = $this->createEvent(['search' => $searchTerm]);

        $this->subscriber->onCriteria($event);

        static::assertEmpty($event->getCriteria()->getQueries());
    }

    public static function searchTermProvider(): array
    {
        return [
            'empty string' => [''],
            'whitespace' => ['   '],
        ];
    }

    public function testSuggestEventAlsoAddsScoreQuery(): void
    {
        $this->filterHelper->expects(static::once())
            ->method('parseFilterValues')
            ->with(null)
            ->willReturn([]);

        $event = new ProductSuggestCriteriaEvent(
            new Request(['search' => 'books']),
            new Criteria(),
            $this->createMock(SalesChannelContext::class)
        );

        $this->subscriber->onCriteria($event);

        $queries = $event->getCriteria()->getQueries();
        static::assertCount(1, $queries);
        static::assertSame(500, (int) $queries[0]->getScore());
    }

    /**
     * @param array<string, mixed> $query
     */
    private function createEvent(array $query = []): ProductSearchCriteriaEvent
    {
        return new ProductSearchCriteriaEvent(
            new Request($query),
            new Criteria(),
            $this->createMock(SalesChannelContext::class)
        );
    }
}
