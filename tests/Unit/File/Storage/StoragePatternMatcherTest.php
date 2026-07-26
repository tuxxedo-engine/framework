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

namespace Unit\File\Storage;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\File\Storage\StoragePatternMatcher;

class StoragePatternMatcherTest extends TestCase
{
    /**
     * @return \Generator<int, array{0: string, 1: string, 2: bool}>
     */
    public static function matchProvider(): \Generator
    {
        yield [
            '**',
            'anything',
            true,
        ];

        yield [
            '**',
            'avatars/2026/x.png',
            true,
        ];

        yield [
            '**',
            '',
            true,
        ];

        yield [
            'avatars/**',
            'avatars/x.png',
            true,
        ];

        yield [
            'avatars/**',
            'avatars/2026/x.png',
            true,
        ];

        yield [
            'avatars/**',
            'users/x.png',
            false,
        ];

        yield [
            'avatars/*',
            'avatars/x.png',
            true,
        ];

        yield [
            'avatars/*',
            'avatars/2026/x.png',
            false,
        ];

        yield [
            '**/*.png',
            'x.png',
            true,
        ];

        yield [
            '**/*.png',
            'avatars/x.png',
            true,
        ];

        yield [
            '**/*.png',
            'avatars/2026/x.png',
            true,
        ];

        yield [
            '**/*.png',
            'x.jpg',
            false,
        ];

        yield [
            'a/*/x.png',
            'a/2026/x.png',
            true,
        ];

        yield [
            'a/*/x.png',
            'a/x.png',
            false,
        ];

        yield [
            'a/*/x.png',
            'a/2026/07/x.png',
            false,
        ];

        yield [
            '?.txt',
            'a.txt',
            true,
        ];

        yield [
            '?.txt',
            'ab.txt',
            false,
        ];

        yield [
            'exact/path.txt',
            'exact/path.txt',
            true,
        ];

        yield [
            'exact/path.txt',
            'exact/Path.txt',
            false,
        ];
    }

    #[DataProvider('matchProvider')]
    public function testMatches(
        string $pattern,
        string $key,
        bool $expected,
    ): void {
        self::assertSame(
            $expected,
            StoragePatternMatcher::matches(
                pattern: $pattern,
                key: $key,
            ),
        );
    }

    public function testMatchingIsCaseSensitive(): void
    {
        self::assertTrue(
            StoragePatternMatcher::matches(
                pattern: 'Avatars/*.png',
                key: 'Avatars/x.png',
            ),
        );

        self::assertFalse(
            StoragePatternMatcher::matches(
                pattern: 'Avatars/*.png',
                key: 'avatars/x.png',
            ),
        );
    }

    public function testLiteralPrefixWithoutWildcards(): void
    {
        self::assertSame(
            'exact/path.txt',
            StoragePatternMatcher::literalPrefix(
                pattern: 'exact/path.txt',
            ),
        );
    }

    public function testLiteralPrefixUpToFirstStar(): void
    {
        self::assertSame(
            'avatars/',
            StoragePatternMatcher::literalPrefix(
                pattern: 'avatars/*.png',
            ),
        );
    }

    public function testLiteralPrefixUpToFirstDoubleStar(): void
    {
        self::assertSame(
            'avatars/',
            StoragePatternMatcher::literalPrefix(
                pattern: 'avatars/**',
            ),
        );
    }

    public function testLiteralPrefixUpToFirstQuestionMark(): void
    {
        self::assertSame(
            'file-',
            StoragePatternMatcher::literalPrefix(
                pattern: 'file-?.txt',
            ),
        );
    }

    public function testLiteralPrefixEmptyForLeadingWildcard(): void
    {
        self::assertSame(
            '',
            StoragePatternMatcher::literalPrefix(
                pattern: '**/foo.txt',
            ),
        );

        self::assertSame(
            '',
            StoragePatternMatcher::literalPrefix(
                pattern: '*.txt',
            ),
        );
    }

    public function testRegexMetacharactersAreLiteralInPattern(): void
    {
        self::assertTrue(
            StoragePatternMatcher::matches(
                pattern: 'file.name+with.dots',
                key: 'file.name+with.dots',
            ),
        );

        self::assertFalse(
            StoragePatternMatcher::matches(
                pattern: 'file.name+with.dots',
                key: 'fileXnameXwithXdots',
            ),
        );
    }
}
