<?php

declare(strict_types=1);

namespace WbmProductType\Tests\Unit\Elasticsearch\Admin;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\TableNotFoundException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\Event\NestedEventCollection;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Elasticsearch\Admin\Indexer\AbstractAdminIndexer;
use WbmProductType\Elasticsearch\Admin\ProductAdminSearchIndexerDecorator;

#[CoversClass(ProductAdminSearchIndexerDecorator::class)]
#[AllowMockObjectsWithoutExpectations]
class ProductAdminSearchIndexerDecoratorTest extends TestCase
{
    public function testFetchEnrichesTextWithProductType(): void
    {
        $inner = $this->createMock(AbstractAdminIndexer::class);
        $inner->method('fetch')->willReturn([
            'abc123' => ['id' => 'abc123', 'text' => 'some tags', 'textBoosted' => 'product name sku-1'],
            'def456' => ['id' => 'def456', 'text' => 'other tags', 'textBoosted' => 'other product sku-2'],
        ]);

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAllAssociative')->willReturn([
            ['product_id' => 'abc123', 'product_type' => 'Bücher'],
        ]);

        $decorator = new ProductAdminSearchIndexerDecorator($inner, $connection);
        $documents = $decorator->fetch(['abc123', 'def456']);

        static::assertStringContainsString('bücher', $documents['abc123']['text']);
        static::assertStringContainsString('bücher', $documents['abc123']['textBoosted']);

        // Product without extension — text unchanged
        static::assertSame('other tags', $documents['def456']['text']);
        static::assertSame('other product sku-2', $documents['def456']['textBoosted']);
    }

    public function testFetchHandlesMissingTable(): void
    {
        $inner = $this->createMock(AbstractAdminIndexer::class);
        $inner->method('fetch')->willReturn([
            'abc123' => ['id' => 'abc123', 'text' => 'tags', 'textBoosted' => 'name'],
        ]);

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAllAssociative')->willThrowException(
            $this->createMock(TableNotFoundException::class)
        );

        $decorator = new ProductAdminSearchIndexerDecorator($inner, $connection);
        $documents = $decorator->fetch(['abc123']);

        static::assertSame('tags', $documents['abc123']['text']);
        static::assertSame('name', $documents['abc123']['textBoosted']);
    }

    public function testFetchSkipsQueryForEmptyIds(): void
    {
        $inner = $this->createMock(AbstractAdminIndexer::class);
        $inner->method('fetch')->willReturn([]);

        $connection = $this->createMock(Connection::class);
        $connection->expects(static::never())->method('fetchAllAssociative');

        $decorator = new ProductAdminSearchIndexerDecorator($inner, $connection);

        static::assertSame([], $decorator->fetch([]));
    }

    public function testGetUpdatedIdsMergesExtensionProductIds(): void
    {
        $innerProductId = Uuid::randomHex();
        $extensionId = Uuid::randomHex();
        $extensionProductId = Uuid::randomHex();

        $inner = $this->createMock(AbstractAdminIndexer::class);
        $inner->method('getUpdatedIds')->willReturn([$innerProductId]);

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchFirstColumn')->willReturn([$extensionProductId]);

        $decorator = new ProductAdminSearchIndexerDecorator($inner, $connection);

        $event = $this->createContainerEvent('wbm_product_extension', $extensionId, ['productType' => 'Bücher']);
        $ids = $decorator->getUpdatedIds($event);

        static::assertContains($innerProductId, $ids);
        static::assertContains($extensionProductId, $ids);
    }

    public function testGetUpdatedIdsReturnsOnlyInnerWhenNoExtensionChange(): void
    {
        $innerProductId = Uuid::randomHex();

        $inner = $this->createMock(AbstractAdminIndexer::class);
        $inner->method('getUpdatedIds')->willReturn([$innerProductId]);

        $connection = $this->createMock(Connection::class);
        $connection->expects(static::never())->method('fetchFirstColumn');

        $decorator = new ProductAdminSearchIndexerDecorator($inner, $connection);

        // Event for a different entity
        $event = $this->createContainerEvent('product', Uuid::randomHex(), ['name' => 'Test']);
        $ids = $decorator->getUpdatedIds($event);

        static::assertSame([$innerProductId], $ids);
    }

    public function testGetDecoratedReturnsInner(): void
    {
        $inner = $this->createMock(AbstractAdminIndexer::class);
        $decorator = new ProductAdminSearchIndexerDecorator($inner, $this->createMock(Connection::class));

        static::assertSame($inner, $decorator->getDecorated());
    }

    public function testDelegatesGetEntity(): void
    {
        $inner = $this->createMock(AbstractAdminIndexer::class);
        $inner->method('getEntity')->willReturn('product');

        $decorator = new ProductAdminSearchIndexerDecorator($inner, $this->createMock(Connection::class));

        static::assertSame('product', $decorator->getEntity());
    }

    private function createContainerEvent(string $entityName, string $id, array $payload): EntityWrittenContainerEvent
    {
        $payload['id'] = $id;
        $context = Context::createDefaultContext();

        $writeResult = new EntityWriteResult(
            $id,
            $payload,
            $entityName,
            EntityWriteResult::OPERATION_UPDATE,
            new EntityExistence($entityName, ['id' => $id], true, false, false, []),
        );

        $writtenEvent = new EntityWrittenEvent($entityName, [$writeResult], $context);

        return new EntityWrittenContainerEvent($context, new NestedEventCollection([$writtenEvent]), []);
    }
}
