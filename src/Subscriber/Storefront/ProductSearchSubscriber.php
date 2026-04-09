<?php

declare(strict_types=1);

namespace WbmProductType\Subscriber\Storefront;

use Shopware\Core\Content\Product\Events\ProductListingCriteriaEvent;
use Shopware\Core\Content\Product\Events\ProductSearchCriteriaEvent;
use Shopware\Core\Content\Product\Events\ProductSuggestCriteriaEvent;
use Shopware\Core\Framework\Adapter\Request\RequestParamHelper;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Query\ScoreQuery;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use WbmProductType\Helper\ProductTypeFilterHelper;

class ProductSearchSubscriber implements EventSubscriberInterface
{
    private const PRODUCT_TYPE_SEARCH_SCORE = 500;

    public function __construct(private readonly ProductTypeFilterHelper $filterHelper)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductSearchCriteriaEvent::class => 'onCriteria',
            ProductSuggestCriteriaEvent::class => 'onCriteria',
        ];
    }

    public function onCriteria(ProductListingCriteriaEvent $event): void
    {
        $criteria = $event->getCriteria();
        $request = $event->getRequest();

        $criteria->addAssociation('wbmExtension');
        $criteria->addAggregation(new TermsAggregation('product_type', 'wbmExtension.productType'));

        $values = $this->filterHelper->parseFilterValues(RequestParamHelper::get($request, 'productType'));
        if ($values !== []) {
            $criteria->addFilter(new EqualsAnyFilter('wbmExtension.productType', $values));
        }

        $term = trim((string) RequestParamHelper::get($request, 'search'));
        if ($term !== '') {
            $criteria->addQuery(
                new ScoreQuery(
                    new EqualsFilter('wbmExtension.productType', $term),
                    self::PRODUCT_TYPE_SEARCH_SCORE
                )
            );
        }
    }
}
