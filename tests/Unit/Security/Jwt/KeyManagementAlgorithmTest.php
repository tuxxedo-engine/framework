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

namespace Unit\Security\Jwt;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Security\Jwt\KeyManagementAlgorithm;

class KeyManagementAlgorithmTest extends TestCase
{
    /**
     * @return array<string, array{KeyManagementAlgorithm, string}>
     */
    public static function providesIdentifiers(): array
    {
        return [
            'DIR is lowercase' => [
                KeyManagementAlgorithm::DIR,
                'dir',
            ],
            'A128KW matches enum name' => [
                KeyManagementAlgorithm::A128KW,
                'A128KW',
            ],
            'A192KW matches enum name' => [
                KeyManagementAlgorithm::A192KW,
                'A192KW',
            ],
            'A256KW matches enum name' => [
                KeyManagementAlgorithm::A256KW,
                'A256KW',
            ],
        ];
    }

    /**
     * @return array<string, array{KeyManagementAlgorithm, string}>
     */
    public static function providesMatchingIdentifiers(): array
    {
        return [
            'exact case A128KW' => [
                KeyManagementAlgorithm::A128KW,
                'A128KW',
            ],
            'lowercase A128KW' => [
                KeyManagementAlgorithm::A128KW,
                'a128kw',
            ],
            'mixed case DIR' => [
                KeyManagementAlgorithm::DIR,
                'Dir',
            ],
            'uppercase DIR' => [
                KeyManagementAlgorithm::DIR,
                'DIR',
            ],
            'lowercase dir' => [
                KeyManagementAlgorithm::DIR,
                'dir',
            ],
        ];
    }

    /**
     * @return array<string, array{KeyManagementAlgorithm, string}>
     */
    public static function providesNonMatchingIdentifiers(): array
    {
        return [
            'unrelated string' => [
                KeyManagementAlgorithm::A128KW,
                'none',
            ],
            'empty string' => [
                KeyManagementAlgorithm::A128KW,
                '',
            ],
            'wrong variant' => [
                KeyManagementAlgorithm::A128KW,
                'A256KW',
            ],
            'family prefix' => [
                KeyManagementAlgorithm::A128KW,
                'A128',
            ],
            'DIR vs KW cross' => [
                KeyManagementAlgorithm::DIR,
                'A128KW',
            ],
        ];
    }

    #[DataProvider('providesIdentifiers')]
    public function testIdentifier(
        KeyManagementAlgorithm $algorithm,
        string $expected,
    ): void {
        self::assertSame(
            $expected,
            $algorithm->identifier(),
        );
    }

    #[DataProvider('providesMatchingIdentifiers')]
    public function testIsReturnsTrueForMatching(
        KeyManagementAlgorithm $algorithm,
        string $identifier,
    ): void {
        self::assertTrue(
            $algorithm->is(
                identifier: $identifier,
            ),
        );
    }

    #[DataProvider('providesNonMatchingIdentifiers')]
    public function testIsReturnsFalseForNonMatching(
        KeyManagementAlgorithm $algorithm,
        string $identifier,
    ): void {
        self::assertFalse(
            $algorithm->is(
                identifier: $identifier,
            ),
        );
    }
}
