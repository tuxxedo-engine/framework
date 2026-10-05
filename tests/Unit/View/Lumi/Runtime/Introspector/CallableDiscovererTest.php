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

namespace Unit\View\Lumi\Runtime\Introspector;

use Fixture\View\Lumi\Runtime\Introspector\StubFilterClass;
use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableDiscoverer;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableKind;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableMetadataInterface;

class CallableDiscovererTest extends TestCase
{
    public function testDiscoverFiltersExtractsAllAttributedMethods(): void
    {
        $discoverer = new CallableDiscoverer();
        $metadata = $discoverer->discoverFilters(StubFilterClass::class);

        self::assertCount(2, $metadata);
    }

    public function testDiscoveredFilterCarriesTypedAttributeKind(): void
    {
        $discoverer = new CallableDiscoverer();
        $metadata = $discoverer->discoverFilters(StubFilterClass::class);

        self::assertSame(CallableKind::TYPED_ATTRIBUTE, $metadata[0]->kind);
    }

    public function testDiscoveredFilterWithoutContextParameterHasNullIndex(): void
    {
        $discoverer = new CallableDiscoverer();
        $metadata = $discoverer->discoverFilters(StubFilterClass::class);
        $upper = $this->findByName($metadata, 'stub_upper');

        self::assertNotNull($upper);
        self::assertFalse($upper->wantsContext);
        self::assertNull($upper->contextParameterIndex);
    }

    public function testDiscoveredFilterWithContextAttributeCapturesIndex(): void
    {
        $discoverer = new CallableDiscoverer();
        $metadata = $discoverer->discoverFilters(StubFilterClass::class);
        $withContext = $this->findByName($metadata, 'stub_context');

        self::assertNotNull($withContext);
        self::assertTrue($withContext->wantsContext);
        self::assertSame(1, $withContext->contextParameterIndex);
    }

    public function testDiscoveredFilterCapturesAliases(): void
    {
        $discoverer = new CallableDiscoverer();
        $metadata = $discoverer->discoverFilters(StubFilterClass::class);
        $withContext = $this->findByName($metadata, 'stub_context');

        self::assertNotNull($withContext);
        self::assertSame(
            [
                'stub_ctx',
            ],
            $withContext->aliases,
        );
    }

    /**
     * @param list<CallableMetadataInterface> $metadata
     */
    private function findByName(
        array $metadata,
        string $name,
    ): ?CallableMetadataInterface {
        foreach ($metadata as $entry) {
            if ($entry->name === $name) {
                return $entry;
            }
        }

        return null;
    }
}
