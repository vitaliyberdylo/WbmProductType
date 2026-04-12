<?php

declare(strict_types=1);

namespace WbmProductType\Subscriber\Storefront;

use Shopware\Core\Content\Product\Events\ProductListingCriteriaEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Elasticsearch\Framework\ElasticsearchHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use WbmProductType\Helper\ProductTypeFilterHelper;

readonly class ProductListingSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ProductTypeFilterHelper $filterHelper,
        private readonly ElasticsearchHelper $elasticsearchHelper
    ) {
    }
    public static function getSubscribedEvents(): array
    {
        return [
            ProductListingCriteriaEvent::class => 'onCriteria',
        ];
    }

    public function onCriteria(ProductListingCriteriaEvent $event): void
    {
        if (!$this->elasticsearchHelper->allowIndexing()) {
            return;
        }

        $criteria = $event->getCriteria();

        $criteria->addAssociation('wbmExtension');
        $criteria->addAggregation(new TermsAggregation('product_type', 'wbmExtension.productType'));

        $raw = $event->getRequest()->query->all()['productType'] ?? null;
        $values = $this->filterHelper->parseFilterValues($raw);
        if ($values) {
            $criteria->addFilter(new EqualsAnyFilter('wbmExtension.productType', $values));
        }
    }
}
