import FilterProductTypePlugin from './plugin/filter-product-type.plugin';

const PluginManager = window.PluginManager;

PluginManager.register('FilterProductType', FilterProductTypePlugin, '[data-filter-product-type]');
