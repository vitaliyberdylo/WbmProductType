const { Component, Context } = Shopware;
const { Criteria } = Shopware.Data;

Component.override('sw-product-list', {
    data() {
        return {
            wbmProductTypeOptions: [],
        };
    },

    computed: {
        productColumns() {
            return [
                ...this.$super('productColumns'),
                {
                    property: 'extensions.wbmExtension.productType',
                    label: this.$t('wbm-product-type.productList.columnProductType'),
                    rawData: true,
                    allowResize: true,
                },
            ];
        },

        productCriteria() {
            const criteria = this.$super('productCriteria');
            criteria.addAssociation('wbmExtension');

            if (this.term) {
                criteria.addQuery(
                    Criteria.equals('wbmExtension.productType', this.term),
                    500,
                );
                criteria.addQuery(
                    Criteria.contains('wbmExtension.productType', this.term),
                    200,
                );
            }

            return criteria;
        },

        listFilterOptions() {
            const options = this.$super('listFilterOptions');

            options['wbm-product-type-filter'] = {
                property: 'wbmExtension.productType',
                label: this.$t('wbm-product-type.productList.columnProductType'),
                placeholder: this.$t('wbm-product-type.productList.filterPlaceholder'),
                type: 'multi-select-filter',
                options: this.wbmProductTypeOptions,
            };

            return options;
        },
    },

    created() {
        if (!this.defaultFilters.includes('wbm-product-type-filter')) {
            this.defaultFilters.push('wbm-product-type-filter');
        }
        this.loadWbmProductTypes();
    },

    methods: {
        async loadWbmProductTypes() {
            const repository = this.repositoryFactory.create('wbm_product_extension');
            const criteria = new Criteria(1, 1);
            criteria.addAggregation(Criteria.terms('productTypes', 'productType', 500));

            const result = await repository.search(criteria, Context.api);
            const aggregation = result.aggregations?.productTypes;

            if (!aggregation?.buckets) {
                return;
            }

            this.wbmProductTypeOptions = aggregation.buckets
                .filter((bucket) => bucket.key)
                .sort((a, b) => a.key.localeCompare(b.key))
                .map((bucket) => ({
                    label: bucket.key,
                    value: bucket.key,
                }));
        },
    },
});
