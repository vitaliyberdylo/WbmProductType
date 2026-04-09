# WBM Product Type

Shopware 6 plugin that adds a dedicated **Product Type** field to products via a separate extension table (`wbm_product_extension`). Includes admin UI integration, storefront listing/search filters, and Elasticsearch support.

## Requirements

| Dependency      | Version  |
|-----------------|----------|
| Shopware 6      | >= 6.7   |
| OpenSearch / ES | Recommended |

## Features

- Separate DAL entity `wbm_product_extension` with one-to-one product association
- `productType` (string) and `apiProductId` (int) fields, both API-aware
- Admin: product type displayed in product detail (specifications tab) and product list
- Storefront: multi-select filter in product listing sidebar
- Search: product type boosted in storefront search results
- Elasticsearch: storefront and admin search indexer decorators
- Translations: EN and DE snippets for admin and storefront

## Installation

### Via Composer

```bash
composer require wbm/product-type
```

The plugin will be installed into `custom/plugins/WbmProductType/`.

### Activate and migrate

```bash
bin/console plugin:refresh
bin/console plugin:install --activate WbmProductType
```

The migration runs automatically on install and creates the `wbm_product_extension` table.

### Build frontend assets

```bash
bin/build-js.sh 
bin/console theme:compile
bin/console cache:clear
```

### Elasticsearch / OpenSearch (recommended)

The plugin decorates storefront and admin ES indexers to index `productType`. Make sure ES/OpenSearch is enabled and rebuild the index:

```bash
bin/console es:index --no-queue
```

## Configuration

No plugin configuration required. The product type field is immediately available after activation.

### Setting product type

- **Admin:** Open any product -> Specifications tab -> "Product Type" field
- **API:** `POST /api/wbm-product-extension` or include `wbmExtension.productType` when writing products

### Storefront filter

The product type filter appears automatically in the product listing sidebar when products have type values assigned.

## Testing

### PHP Unit tests

Run plugin unit tests:

```bash
docker exec wbm-shopware-web-1 bash -c "APP_ENV=test php vendor/bin/phpunit --configuration custom/plugins/WbmProductType/phpunit.xml"
```
