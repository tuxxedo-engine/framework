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

use Fixture\View\Lumi\Runtime\Introspector\StubArityClass;
use Fixture\View\Lumi\Runtime\Introspector\StubContextOnlyFilterClass;
use Fixture\View\Lumi\Runtime\Introspector\StubFilterClass;
use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableDiscoverer;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableMetadataInterface;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableParameterInterface;
use Tuxxedo\View\Lumi\Runtime\Introspector\IntrospectorException;

class CallableDiscovererTest extends TestCase
{
    public function testDiscoverFiltersExtractsAllAttributedMethods(): void
    {
        $metadata = CallableDiscoverer::discoverFilters(StubFilterClass::class);

        self::assertCount(2, $metadata);
    }

    public function testDiscoveredFilterWithoutContextParameterHasNullIndex(): void
    {
        $metadata = CallableDiscoverer::discoverFilters(StubFilterClass::class);
        $upper = $this->findByName($metadata, 'stub_upper');

        self::assertNotNull($upper);
        self::assertFalse($upper->wantsContext);
        self::assertNull($upper->contextParameterIndex);
    }

    public function testDiscoveredFilterWithContextAttributeCapturesIndex(): void
    {
        $metadata = CallableDiscoverer::discoverFilters(StubFilterClass::class);
        $withContext = $this->findByName($metadata, 'stub_context');

        self::assertNotNull($withContext);
        self::assertTrue($withContext->wantsContext);
        self::assertSame(1, $withContext->contextParameterIndex);
    }

    public function testDiscoveredFilterCapturesAliases(): void
    {
        $metadata = CallableDiscoverer::discoverFilters(StubFilterClass::class);
        $withContext = $this->findByName($metadata, 'stub_context');

        self::assertNotNull($withContext);
        self::assertSame(
            [
                'stub_ctx',
            ],
            $withContext->aliases,
        );
    }

    public function testDiscoveredFilterArgumentBoundsExcludeContext(): void
    {
        $wrap = $this->findByName(CallableDiscoverer::discoverFilters(StubArityClass::class), 'stub_wrap');

        self::assertNotNull($wrap);
        self::assertSame(2, $wrap->minimumArguments);
        self::assertSame(3, $wrap->maximumArguments);
    }

    public function testDiscoveredVariadicFilterHasNoMaximumArguments(): void
    {
        $join = $this->findByName(CallableDiscoverer::discoverFilters(StubArityClass::class), 'stub_join');

        self::assertNotNull($join);
        self::assertSame(0, $join->minimumArguments);
        self::assertNull($join->maximumArguments);
    }

    public function testMethodWithBothAttributesIsDiscoveredAsFunctionAndFilter(): void
    {
        $function = $this->findByName(CallableDiscoverer::discoverFunctions(StubArityClass::class), 'stub_wrap');
        $filter = $this->findByName(CallableDiscoverer::discoverFilters(StubArityClass::class), 'stub_wrap');

        self::assertNotNull($function);
        self::assertNotNull($filter);
        self::assertSame('wrap', $function->methodName);
        self::assertSame('wrap', $filter->methodName);
    }

    public function testDiscoverFiltersRejectsFilterWithoutValueParameter(): void
    {
        try {
            CallableDiscoverer::discoverFilters(StubContextOnlyFilterClass::class);

            self::fail('Expected IntrospectorException');
        } catch (IntrospectorException $exception) {
            self::assertStringContainsString('stub_context_only', $exception->getMessage());
        }
    }

    public function testDiscoveredFilterParametersSkipContextAndUseArgumentPositions(): void
    {
        $wrap = $this->findByName(CallableDiscoverer::discoverFilters(StubArityClass::class), 'stub_wrap');

        self::assertNotNull($wrap);
        self::assertSame(
            [
                [
                    'value',
                    0,
                    false,
                    false,
                ],
                [
                    'prefix',
                    1,
                    false,
                    false,
                ],
                [
                    'suffix',
                    2,
                    true,
                    false,
                ],
            ],
            $this->describeParameters($wrap),
        );
    }

    public function testDiscoveredFunctionCapturesParameters(): void
    {
        $wrap = $this->findByName(CallableDiscoverer::discoverFunctions(StubArityClass::class), 'stub_wrap');

        self::assertNotNull($wrap);
        self::assertSame(
            [
                'value',
                'prefix',
                'suffix',
            ],
            \array_map(
                static fn (CallableParameterInterface $parameter): string => $parameter->name,
                $wrap->parameters,
            ),
        );
    }

    public function testDiscoveredVariadicFilterMarksVariadicParameter(): void
    {
        $join = $this->findByName(CallableDiscoverer::discoverFilters(StubArityClass::class), 'stub_join');

        self::assertNotNull($join);
        self::assertSame(
            [
                [
                    'values',
                    0,
                    true,
                    true,
                ],
            ],
            $this->describeParameters($join),
        );
    }

    public function testDiscoveredFilterCapturesContextParameterName(): void
    {
        $withContext = $this->findByName(CallableDiscoverer::discoverFilters(StubFilterClass::class), 'stub_context');
        $wrap = $this->findByName(CallableDiscoverer::discoverFunctions(StubArityClass::class), 'stub_wrap');

        self::assertNotNull($withContext);
        self::assertNotNull($wrap);
        self::assertSame('context', $withContext->contextParameterName);
        self::assertSame('context', $wrap->contextParameterName);
    }

    public function testDiscoveredFilterWithoutContextHasNullContextParameterName(): void
    {
        $upper = $this->findByName(CallableDiscoverer::discoverFilters(StubFilterClass::class), 'stub_upper');

        self::assertNotNull($upper);
        self::assertNull($upper->contextParameterName);
    }

    /**
     * @return list<array{0: string, 1: int, 2: bool, 3: bool}>
     */
    private function describeParameters(
        CallableMetadataInterface $metadata,
    ): array {
        return \array_map(
            static fn (CallableParameterInterface $parameter): array => [
                $parameter->name,
                $parameter->position,
                $parameter->optional,
                $parameter->variadic,
            ],
            $metadata->parameters,
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
