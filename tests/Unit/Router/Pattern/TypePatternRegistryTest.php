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

namespace Unit\Router\Pattern;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Router\Pattern\TypePattern;
use Tuxxedo\Router\Pattern\TypePatternInterface;
use Tuxxedo\Router\Pattern\TypePatternRegistry;

class TypePatternRegistryTest extends TestCase
{
    public function testCreateDefaultContainsAllBuiltInPatterns(): void
    {
        $registry = TypePatternRegistry::createDefault();

        self::assertCount(15, $registry->patterns);
    }

    public function testCreateDefaultPatternsAreIndexedByName(): void
    {
        $registry = TypePatternRegistry::createDefault();

        foreach ($registry->patterns as $name => $pattern) {
            self::assertSame($name, $pattern->name);
        }
    }

    public function testGetDefaultsReturnsAllFifteenInstances(): void
    {
        $defaults = TypePatternRegistry::getDefaults();

        self::assertCount(15, $defaults);
    }

    public function testGetDefaultsReturnsTypePatternInterfaceInstances(): void
    {
        foreach (TypePatternRegistry::getDefaults() as $pattern) {
            self::assertInstanceOf(TypePatternInterface::class, $pattern);
        }
    }

    public function testCreateWithDefaultsMergesAdditionalPatterns(): void
    {
        $extra = new TypePattern(
            name: 'custom',
            regex: '[x]+',
        );

        $registry = TypePatternRegistry::createWithDefaults(
            [
                $extra,
            ],
        );

        self::assertCount(16, $registry->patterns);
        self::assertTrue($registry->has('custom'));
    }

    public function testCreateWithDefaultsContainsAllBuiltInPatterns(): void
    {
        $registry = TypePatternRegistry::createWithDefaults(
            [
                new TypePattern(
                    name: 'custom',
                    regex: '[x]+',
                ),
            ],
        );

        foreach (TypePatternRegistry::getDefaults() as $default) {
            self::assertTrue($registry->has($default->name));
        }
    }

    public function testCreateWithoutDefaultsContainsOnlyProvidedPatterns(): void
    {
        $pattern = new TypePattern(
            name: 'only',
            regex: '\d+',
        );

        $registry = TypePatternRegistry::createWithoutDefaults(
            [
                $pattern,
            ],
        );

        self::assertCount(1, $registry->patterns);
        self::assertTrue($registry->has('only'));
    }

    public function testCreateWithoutDefaultsExcludesBuiltIns(): void
    {
        $registry = TypePatternRegistry::createWithoutDefaults(
            [
                new TypePattern(
                    name: 'custom',
                    regex: '[x]+',
                ),
            ],
        );

        foreach (TypePatternRegistry::getDefaults() as $default) {
            self::assertFalse($registry->has($default->name));
        }
    }

    public function testHasReturnsTrueForRegisteredPattern(): void
    {
        $registry = TypePatternRegistry::createDefault();

        self::assertTrue($registry->has('alpha'));
    }

    public function testHasReturnsFalseForUnregisteredPattern(): void
    {
        $registry = TypePatternRegistry::createDefault();

        self::assertFalse($registry->has('nonexistent'));
    }

    public function testGetReturnsPatternForRegisteredName(): void
    {
        $registry = TypePatternRegistry::createDefault();

        $pattern = $registry->get('alpha');

        self::assertInstanceOf(TypePatternInterface::class, $pattern);
        self::assertSame('alpha', $pattern->name);
    }

    public function testGetReturnsNullForUnregisteredName(): void
    {
        $registry = TypePatternRegistry::createDefault();

        self::assertNull($registry->get('nonexistent'));
    }

    public function testLaterPatternOverwritesEarlierOnDuplicateName(): void
    {
        $first = new TypePattern(
            name: 'dupe',
            regex: 'first',
        );

        $second = new TypePattern(
            name: 'dupe',
            regex: 'second',
        );

        $registry = TypePatternRegistry::createWithoutDefaults(
            [
                $first,
                $second,
            ],
        );

        self::assertNotNull($registry->get('dupe'));
        self::assertSame('second', $registry->get('dupe')->regex);
    }

    /**
     * @return \Generator<string, array{0: string, 1: string, 2: bool}>
     */
    public static function providesUuidMatchCases(): \Generator
    {
        yield 'uuid: canonical lowercase' => [
            'uuid',
            '550e8400-e29b-41d4-a716-446655440000',
            true,
        ];

        yield 'uuid: canonical uppercase' => [
            'uuid',
            '550E8400-E29B-41D4-A716-446655440000',
            true,
        ];

        yield 'uuid: mixed case' => [
            'uuid',
            '550E8400-e29b-41D4-a716-446655440000',
            true,
        ];

        yield 'uuid: v7 accepted' => [
            'uuid',
            '018ff8f0-1234-7abc-9def-0123456789ab',
            true,
        ];

        yield 'uuid: nil accepted' => [
            'uuid',
            '00000000-0000-0000-0000-000000000000',
            true,
        ];

        yield 'uuid: all hyphens rejected' => [
            'uuid',
            '------------------------------------',
            false,
        ];

        yield 'uuid: missing hyphens rejected' => [
            'uuid',
            '550e8400e29b41d4a716446655440000',
            false,
        ];

        yield 'uuid: wrong segment length rejected' => [
            'uuid',
            '550e840-e29b-41d4-a716-446655440000',
            false,
        ];

        yield 'uuid: non hex rejected' => [
            'uuid',
            '550e8400-e29b-41d4-a716-44665544000g',
            false,
        ];

        yield 'uuid: trailing junk rejected' => [
            'uuid',
            '550e8400-e29b-41d4-a716-446655440000x',
            false,
        ];

        yield 'uuid: empty rejected' => [
            'uuid',
            '',
            false,
        ];

        yield 'uuid-v4: valid lowercase' => [
            'uuid-v4',
            '550e8400-e29b-41d4-a716-446655440000',
            true,
        ];

        yield 'uuid-v4: valid uppercase' => [
            'uuid-v4',
            '550E8400-E29B-41D4-A716-446655440000',
            true,
        ];

        yield 'uuid-v4: uppercase variant nibble' => [
            'uuid-v4',
            '550e8400-e29b-41d4-B716-446655440000',
            true,
        ];

        yield 'uuid-v4: variant 8' => [
            'uuid-v4',
            '550e8400-e29b-41d4-8716-446655440000',
            true,
        ];

        yield 'uuid-v4: v7 rejected' => [
            'uuid-v4',
            '018ff8f0-1234-7abc-9def-0123456789ab',
            false,
        ];

        yield 'uuid-v4: invalid variant c rejected' => [
            'uuid-v4',
            '550e8400-e29b-41d4-c716-446655440000',
            false,
        ];

        yield 'uuid-v4: v3 rejected' => [
            'uuid-v4',
            '550e8400-e29b-31d4-a716-446655440000',
            false,
        ];

        yield 'uuid-v4: nil rejected' => [
            'uuid-v4',
            '00000000-0000-0000-0000-000000000000',
            false,
        ];

        yield 'uuid-v4: missing hyphens rejected' => [
            'uuid-v4',
            '550e8400e29b41d4a716446655440000',
            false,
        ];

        yield 'uuid-v4: empty rejected' => [
            'uuid-v4',
            '',
            false,
        ];

        yield 'uuid-v7: valid lowercase' => [
            'uuid-v7',
            '018ff8f0-1234-7abc-9def-0123456789ab',
            true,
        ];

        yield 'uuid-v7: valid uppercase' => [
            'uuid-v7',
            '018FF8F0-1234-7ABC-9DEF-0123456789AB',
            true,
        ];

        yield 'uuid-v7: uppercase variant nibble' => [
            'uuid-v7',
            '018ff8f0-1234-7abc-Bdef-0123456789ab',
            true,
        ];

        yield 'uuid-v7: variant 8' => [
            'uuid-v7',
            '018ff8f0-1234-7abc-8def-0123456789ab',
            true,
        ];

        yield 'uuid-v7: v4 rejected' => [
            'uuid-v7',
            '550e8400-e29b-41d4-a716-446655440000',
            false,
        ];

        yield 'uuid-v7: invalid variant c rejected' => [
            'uuid-v7',
            '018ff8f0-1234-7abc-cdef-0123456789ab',
            false,
        ];

        yield 'uuid-v7: v6 rejected' => [
            'uuid-v7',
            '018ff8f0-1234-6abc-9def-0123456789ab',
            false,
        ];

        yield 'uuid-v7: nil rejected' => [
            'uuid-v7',
            '00000000-0000-0000-0000-000000000000',
            false,
        ];

        yield 'uuid-v7: missing hyphens rejected' => [
            'uuid-v7',
            '018ff8f012347abc9def0123456789ab',
            false,
        ];

        yield 'uuid-v7: empty rejected' => [
            'uuid-v7',
            '',
            false,
        ];
    }

    #[DataProvider('providesUuidMatchCases')]
    public function testUuidPatternRegexMatchesExpected(
        string $patternName,
        string $value,
        bool $shouldMatch,
    ): void {
        $pattern = TypePatternRegistry::createDefault()->get($patternName);

        self::assertNotNull($pattern);

        $matched = \preg_match('/^' . $pattern->regex . '$/', $value) === 1;

        self::assertSame($shouldMatch, $matched);
    }
}
