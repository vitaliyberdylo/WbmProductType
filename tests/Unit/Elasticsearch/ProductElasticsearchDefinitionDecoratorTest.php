<?php

declare(strict_types=1);

namespace WbmProductType\Tests\Unit\Elasticsearch;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\TableNotFoundException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Elasticsearch\Product\ElasticsearchProductDefinition;
use WbmProductType\Elasticsearch\ProductElasticsearchDefinitionDecorator;

#[CoversClass(ProductElasticsearchDefinitionDecorator::class)]
#[AllowMockObjectsWithoutExpectations]
class ProductElasticsearchDefinitionDecoratorTest extends TestCase
{
    public function testMappingPreservesParentAndAddsWbmExtension(): void
    {
        $inner = $this->createMock(ElasticsearchProductDefinition::class);
        $inner->method('getMapping')->willReturn([
            'properties' => [
                'name' => ['type' => 'text'],
                'price' => ['type' => 'double']
            ],
        ]);

        $connection = $this->createMock(Connection::class);

        $decorator = new ProductElasticsearchDefinitionDecorator($inner, $connection);

        $mapping = $decorator->getMapping(Context::createDefaultContext());

        // Parent properties preserved
        static::assertSame(['type' => 'text'], $mapping['properties']['name']);
        static::assertSame(['type' => 'double'], $mapping['properties']['price']);

        // wbmExtension added
        static::assertSame('nested', $mapping['properties']['wbmExtension']['type']);
        static::assertSame('keyword', $mapping['properties']['wbmExtension']['properties']['productType']['type']);
        static::assertSame(255, $mapping['properties']['wbmExtension']['properties']['productType']['ignore_above']);
    }

    public function testFetchEnrichesDocumentsWithProductType(): void
    {
        $inner = $this->createMock(ElasticsearchProductDefinition::class);
        $inner->method('fetch')->willReturn([
            'abc123' => ['name' => 'Product 1'],
            'def456' => ['name' => 'Product 2'],
        ]);

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAllAssociative')->willReturn([
            ['product_id' => 'abc123', 'product_type' => 'Books'],
        ]);

        $decorator = new ProductElasticsearchDefinitionDecorator($inner, $connection);
        $documents = $decorator->fetch(['abc123', 'def456'], Context::createDefaultContext());

        static::assertSame('Books', $documents['abc123']['wbmExtension']['productType']);
        static::assertArrayNotHasKey('wbmExtension', $documents['def456']);
    }

    public function testFetchHandlesMissingTable(): void
    {
        $inner = $this->createMock(ElasticsearchProductDefinition::class);
        $inner->method('fetch')->willReturn([
            'abc123' => ['name' => 'Product 1'],
        ]);

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAllAssociative')->willThrowException(
            $this->createMock(TableNotFoundException::class)
        );

        $decorator = new ProductElasticsearchDefinitionDecorator($inner, $connection);
        $documents = $decorator->fetch(['abc123'], Context::createDefaultContext());

        static::assertArrayNotHasKey('wbmExtension', $documents['abc123']);
    }

    public function testFetchSkipsQueryForEmptyIds(): void
    {
        $inner = $this->createMock(ElasticsearchProductDefinition::class);
        $inner->method('fetch')->willReturn([]);

        $connection = $this->createMock(Connection::class);
        $connection->expects(static::never())->method('fetchAllAssociative');

        $decorator = new ProductElasticsearchDefinitionDecorator($inner, $connection);

        static::assertSame([], $decorator->fetch([], Context::createDefaultContext()));
    }
}
