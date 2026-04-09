import enGB from '../snippet/en-GB/wbm-product-type.json';
import deDE from '../snippet/de-DE/wbm-product-type.json';

import './extension/sw-product-detail';
import './extension/sw-product-detail-specifications';
import './extension/sw-product-list';

Shopware.Locale.extend('en-GB', enGB);
Shopware.Locale.extend('de-DE', deDE);

const { searchRankingPoint } = Shopware.Service('searchRankingService');
const productModule = Shopware.Module.getModuleByEntityName('product');

if (productModule?.manifest?.defaultSearchConfiguration) {
    productModule.manifest.defaultSearchConfiguration.wbmExtension = {
        productType: {
            _searchable: true,
            _score: searchRankingPoint.HIGH_SEARCH_RANKING,
        },
    };
}
