<?php

declare(strict_types=1);

namespace WbmProductType\Tests\Unit\Helper;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WbmProductType\Helper\ProductTypeFilterHelper;

#[CoversClass(ProductTypeFilterHelper::class)]
#[AllowMockObjectsWithoutExpectations]
class ProductTypeFilterHelperTest extends TestCase
{
    private ProductTypeFilterHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new ProductTypeFilterHelper();
    }

    public function testReturnsEmptyArrayForNull(): void
    {
        static::assertSame([], $this->helper->parseFilterValues(null));
    }

    #[DataProvider('validInputProvider')]
    public function testParsesValidInput(mixed $input, array $expected): void
    {
        static::assertSame($expected, $this->helper->parseFilterValues($input));
    }

    #[DataProvider('emptyInputProvider')]
    public function testFiltersEmptyValues(mixed $input): void
    {
        static::assertSame([], $this->helper->parseFilterValues($input));
    }

    public function testTrimsAndDeduplicatesValues(): void
    {
        $result = $this->helper->parseFilterValues('  books  | books |  cds  |  dvds  | cds  ');

        static::assertSame(['books', 'cds', 'dvds'], $result);
    }

    public function testPreservesOrder(): void
    {
        $result = $this->helper->parseFilterValues('zebra|apple|banana|apple');

        static::assertSame(['zebra', 'apple', 'banana'], $result);
    }

    /**
     * @return iterable<string, array{mixed, array<string>}>
     */
    public static function validInputProvider(): iterable
    {
        yield 'string with pipe' => ['books|cds', ['books', 'cds']];
        yield 'array of strings' => [['books', 'cds'], ['books', 'cds']];
        yield 'single value string' => ['books', ['books']];
        yield 'single value array' => [['books'], ['books']];
        yield 'string with spaces' => ['books | cds', ['books', 'cds']];
        yield 'empty string' => ['', []];
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function emptyInputProvider(): iterable
    {
        yield 'null' => [null];
        yield 'empty string' => [''];
        yield 'whitespace only' => ['   '];
        yield 'pipe only' => ['|'];
        yield 'pipes and spaces' => ['  |  |  '];
        yield 'array with empty strings' => [['', '   ', '']];
        yield 'array with nulls' => [null];
    }
}
