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
use Tuxxedo\Security\Jwt\ContentEncryptionAlgorithm;

class ContentEncryptionAlgorithmTest extends TestCase
{
    /**
     * @return array<string, array{ContentEncryptionAlgorithm, string}>
     */
    public static function providesIdentifiers(): array
    {
        return [
            'A128GCM' => [
                ContentEncryptionAlgorithm::A128GCM,
                'A128GCM',
            ],
            'A192GCM' => [
                ContentEncryptionAlgorithm::A192GCM,
                'A192GCM',
            ],
            'A256GCM' => [
                ContentEncryptionAlgorithm::A256GCM,
                'A256GCM',
            ],
            'A128CBC-HS256 is hyphenated' => [
                ContentEncryptionAlgorithm::A128CBC_HS256,
                'A128CBC-HS256',
            ],
            'A192CBC-HS384 is hyphenated' => [
                ContentEncryptionAlgorithm::A192CBC_HS384,
                'A192CBC-HS384',
            ],
            'A256CBC-HS512 is hyphenated' => [
                ContentEncryptionAlgorithm::A256CBC_HS512,
                'A256CBC-HS512',
            ],
        ];
    }

    /**
     * @return array<string, array{ContentEncryptionAlgorithm, string}>
     */
    public static function providesMatchingIdentifiers(): array
    {
        return [
            'exact case A128GCM' => [
                ContentEncryptionAlgorithm::A128GCM,
                'A128GCM',
            ],
            'lowercase A128GCM' => [
                ContentEncryptionAlgorithm::A128GCM,
                'a128gcm',
            ],
            'hyphenated exact case' => [
                ContentEncryptionAlgorithm::A128CBC_HS256,
                'A128CBC-HS256',
            ],
            'hyphenated lowercase' => [
                ContentEncryptionAlgorithm::A256CBC_HS512,
                'a256cbc-hs512',
            ],
        ];
    }

    /**
     * @return array<string, array{ContentEncryptionAlgorithm, string}>
     */
    public static function providesNonMatchingIdentifiers(): array
    {
        return [
            'unrelated string' => [
                ContentEncryptionAlgorithm::A128GCM,
                'none',
            ],
            'empty string' => [
                ContentEncryptionAlgorithm::A128GCM,
                '',
            ],
            'wrong variant' => [
                ContentEncryptionAlgorithm::A128GCM,
                'A256GCM',
            ],
            'family prefix' => [
                ContentEncryptionAlgorithm::A128GCM,
                'A128',
            ],
            'underscore form for hyphenated variant' => [
                ContentEncryptionAlgorithm::A128CBC_HS256,
                'A128CBC_HS256',
            ],
        ];
    }

    /**
     * @return array<string, array{ContentEncryptionAlgorithm, int}>
     */
    public static function providesKeyLengths(): array
    {
        return [
            'A128GCM' => [
                ContentEncryptionAlgorithm::A128GCM,
                16,
            ],
            'A192GCM' => [
                ContentEncryptionAlgorithm::A192GCM,
                24,
            ],
            'A256GCM' => [
                ContentEncryptionAlgorithm::A256GCM,
                32,
            ],
            'A128CBC-HS256 is double the AES half' => [
                ContentEncryptionAlgorithm::A128CBC_HS256,
                32,
            ],
            'A192CBC-HS384 is double the AES half' => [
                ContentEncryptionAlgorithm::A192CBC_HS384,
                48,
            ],
            'A256CBC-HS512 is double the AES half' => [
                ContentEncryptionAlgorithm::A256CBC_HS512,
                64,
            ],
        ];
    }

    /**
     * @return array<string, array{ContentEncryptionAlgorithm, int}>
     */
    public static function providesIvLengths(): array
    {
        return [
            'A128GCM: 12 bytes' => [
                ContentEncryptionAlgorithm::A128GCM,
                12,
            ],
            'A192GCM: 12 bytes' => [
                ContentEncryptionAlgorithm::A192GCM,
                12,
            ],
            'A256GCM: 12 bytes' => [
                ContentEncryptionAlgorithm::A256GCM,
                12,
            ],
            'A128CBC-HS256: 16 bytes' => [
                ContentEncryptionAlgorithm::A128CBC_HS256,
                16,
            ],
            'A192CBC-HS384: 16 bytes' => [
                ContentEncryptionAlgorithm::A192CBC_HS384,
                16,
            ],
            'A256CBC-HS512: 16 bytes' => [
                ContentEncryptionAlgorithm::A256CBC_HS512,
                16,
            ],
        ];
    }

    #[DataProvider('providesIdentifiers')]
    public function testIdentifier(
        ContentEncryptionAlgorithm $algorithm,
        string $expected,
    ): void {
        self::assertSame(
            $expected,
            $algorithm->identifier(),
        );
    }

    #[DataProvider('providesMatchingIdentifiers')]
    public function testIsReturnsTrueForMatching(
        ContentEncryptionAlgorithm $algorithm,
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
        ContentEncryptionAlgorithm $algorithm,
        string $identifier,
    ): void {
        self::assertFalse(
            $algorithm->is(
                identifier: $identifier,
            ),
        );
    }

    #[DataProvider('providesKeyLengths')]
    public function testKeyLengthBytes(
        ContentEncryptionAlgorithm $algorithm,
        int $expected,
    ): void {
        self::assertSame(
            $expected,
            $algorithm->keyLengthBytes(),
        );
    }

    #[DataProvider('providesIvLengths')]
    public function testIvLengthBytes(
        ContentEncryptionAlgorithm $algorithm,
        int $expected,
    ): void {
        self::assertSame(
            $expected,
            $algorithm->ivLengthBytes(),
        );
    }
}
