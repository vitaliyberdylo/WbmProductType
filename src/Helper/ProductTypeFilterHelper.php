<?php

declare(strict_types=1);

namespace WbmProductType\Helper;

/**
 * @internal
 */
class ProductTypeFilterHelper
{
    /**
     * @return list<string>
     */
    public function parseFilterValues(mixed $raw): array
    {
        if ($raw === null) {
            return [];
        }

        $values = \is_array($raw) ? $raw : explode('|', (string) $raw);
        $values = array_map(trim(...), $values);
        $values = array_filter($values, static fn (string $v): bool => $v !== '');

        return array_values(array_unique($values));
    }
}
