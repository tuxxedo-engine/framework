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

use PHPUnit\Framework\TestCase;
use Tuxxedo\Router\Pattern\TypePattern;
use Tuxxedo\Router\Pattern\TypePatternInterface;
use Tuxxedo\Router\Pattern\TypePatternRegistry;

class TypePatternRegistryTest extends TestCase
{
    public function testCreateDefaultContainsAllBuiltInPatterns(): void
    {
        $registry = TypePatternRegistry::createDefault();

        self::assertCount(14, $registry->patterns);
    }

    public function testCreateDefaultPatternsAreIndexedByName(): void
    {
        $registry = TypePatternRegistry::createDefault();

        foreach ($registry->patterns as $name => $pattern) {
            self::assertSame($name, $pattern->name);
        }
    }

    public function testGetDefaultsReturnsAllFourteenInstances(): void
    {
        $defaults = TypePatternRegistry::getDefaults();

        self::assertCount(14, $defaults);
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

        self::assertCount(15, $registry->patterns);
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
}
