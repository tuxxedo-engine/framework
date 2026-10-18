<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2026 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Unit\View\Lumi\Library\Standard;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Library\Standard\StandardLibrary;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableDiscoverer;

class StandardLibraryTest extends TestCase
{
    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function callableNameDataProvider(): \Generator
    {
        foreach (StandardLibrary::functions() as $className) {
            foreach (CallableDiscoverer::discoverFunctions($className) as $metadata) {
                foreach ([$metadata->name, ...$metadata->aliases] as $name) {
                    yield 'function ' . $name => [
                        $name,
                    ];
                }
            }
        }

        foreach (StandardLibrary::filters() as $className) {
            foreach (CallableDiscoverer::discoverFilters($className) as $metadata) {
                foreach ([$metadata->name, ...$metadata->aliases] as $name) {
                    yield 'filter ' . $name => [
                        $name,
                    ];
                }
            }
        }
    }

    #[DataProvider('callableNameDataProvider')]
    public function testCallableNamesAreSnakeCase(
        string $name,
    ): void {
        self::assertMatchesRegularExpression('/^[a-z_][a-z0-9_]*$/', $name);
    }
}
