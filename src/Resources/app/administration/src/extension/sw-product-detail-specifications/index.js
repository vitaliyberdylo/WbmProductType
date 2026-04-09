import template from './sw-product-detail-specifications.html.twig';

const { Component, Context } = Shopware;

Component.override('sw-product-detail-specifications', {
    template,

    computed: {
        wbmExtension() {
            return this.product?.extensions?.wbmExtension ?? null;
        },
    },

    watch: {
        isLoading(newVal, oldVal) {
            if (oldVal === true && newVal === false) {
                this.ensureWbmExtension();
            }
        },
    },

    mounted() {
        if (!this.isLoading) {
            this.ensureWbmExtension();
        }
    },

    methods: {
        ensureWbmExtension() {
            if (this.product?.extensions && !this.product.extensions.wbmExtension) {
                const repository = this.repositoryFactory.create('wbm_product_extension');
                this.product.extensions.wbmExtension = repository.create(Context.api);
            }
        },
    },
});
