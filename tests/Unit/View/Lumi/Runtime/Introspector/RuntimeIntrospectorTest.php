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

use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableKind;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableMetadata;
use Tuxxedo\View\Lumi\Runtime\Introspector\IntrospectorException;
use Tuxxedo\View\Lumi\Runtime\Introspector\RuntimeIntrospector;

class RuntimeIntrospectorTest extends TestCase
{
    public function testEmptyIntrospectorMissesEverything(): void
    {
        $introspector = new RuntimeIntrospector();

        self::assertFalse($introspector->hasFunction('date'));
        self::assertFalse($introspector->hasFilter('upper'));
    }

    public function testGetFunctionThrowsForUnknownName(): void
    {
        $introspector = new RuntimeIntrospector();

        self::expectException(IntrospectorException::class);

        $introspector->getFunction('date');
    }

    public function testGetFilterThrowsForUnknownName(): void
    {
        $introspector = new RuntimeIntrospector();

        self::expectException(IntrospectorException::class);

        $introspector->getFilter('upper');
    }

    public function testHasAnyFunctionsIsFalseForEmptyIntrospector(): void
    {
        self::assertFalse((new RuntimeIntrospector())->hasAnyFunctions());
    }

    public function testHasAnyFunctionsIsTrueWhenAtLeastOneFunctionRegistered(): void
    {
        $introspector = new RuntimeIntrospector(
            functions: [
                $this->makeMetadata(
                    name: 'date',
                ),
            ],
        );

        self::assertTrue($introspector->hasAnyFunctions());
    }

    public function testHasAnyFiltersIsFalseForEmptyIntrospector(): void
    {
        self::assertFalse((new RuntimeIntrospector())->hasAnyFilters());
    }

    public function testHasAnyFiltersIsTrueWhenAtLeastOneFilterRegistered(): void
    {
        $introspector = new RuntimeIntrospector(
            filters: [
                $this->makeMetadata(
                    name: 'upper',
                ),
            ],
        );

        self::assertTrue($introspector->hasAnyFilters());
    }

    public function testGetFunctionByCanonicalName(): void
    {
        $date = $this->makeMetadata(
            name: 'date',
        );

        $introspector = new RuntimeIntrospector(
            functions: [
                $date,
            ],
        );

        self::assertTrue($introspector->hasFunction('date'));
        self::assertSame($date, $introspector->getFunction('date'));
    }

    public function testGetFunctionByAliasReturnsSameCanonicalMetadata(): void
    {
        $date = $this->makeMetadata(
            name: 'date',
            aliases: [
                'time',
                'datetime',
            ],
        );

        $introspector = new RuntimeIntrospector(
            functions: [
                $date,
            ],
        );

        self::assertTrue($introspector->hasFunction('time'));
        self::assertTrue($introspector->hasFunction('datetime'));
        self::assertSame($date, $introspector->getFunction('time'));
        self::assertSame($date, $introspector->getFunction('datetime'));
        self::assertSame('date', $introspector->getFunction('time')->name);
    }

    public function testLookupIsCaseInsensitive(): void
    {
        $date = $this->makeMetadata(
            name: 'Date',
            aliases: [
                'Time',
            ],
        );

        $introspector = new RuntimeIntrospector(
            functions: [
                $date,
            ],
        );

        self::assertTrue($introspector->hasFunction('date'));
        self::assertTrue($introspector->hasFunction('DATE'));
        self::assertTrue($introspector->hasFunction('TIME'));
        self::assertSame($date, $introspector->getFunction('date'));
        self::assertSame($date, $introspector->getFunction('time'));
    }

    public function testGetFilterByCanonicalAndAlias(): void
    {
        $upper = $this->makeMetadata(
            name: 'upper',
            aliases: [
                'uppercase',
            ],
        );

        $introspector = new RuntimeIntrospector(
            filters: [
                $upper,
            ],
        );

        self::assertTrue($introspector->hasFilter('upper'));
        self::assertTrue($introspector->hasFilter('uppercase'));
        self::assertSame($upper, $introspector->getFilter('upper'));
        self::assertSame($upper, $introspector->getFilter('uppercase'));
    }

    public function testFunctionAndFilterNamespacesAreIndependent(): void
    {
        $date = $this->makeMetadata(
            name: 'date',
        );

        $upper = $this->makeMetadata(
            name: 'upper',
        );

        $introspector = new RuntimeIntrospector(
            functions: [
                $date,
            ],
            filters: [
                $upper,
            ],
        );

        self::assertTrue($introspector->hasFunction('date'));
        self::assertFalse($introspector->hasFilter('date'));
        self::assertTrue($introspector->hasFilter('upper'));
        self::assertFalse($introspector->hasFunction('upper'));
    }

    /**
     * @param list<string> $aliases
     */
    private function makeMetadata(
        string $name,
        array $aliases = [],
    ): CallableMetadata {
        return new CallableMetadata(
            name: $name,
            kind: CallableKind::TYPED_ATTRIBUTE,
            className: \stdClass::class,
            methodName: 'call',
            wantsContext: true,
            aliases: $aliases,
        );
    }
}
