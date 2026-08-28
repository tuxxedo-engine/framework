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

namespace Unit\Security\Jwt\Decrypter;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Security\Jwt\Decrypter\AesKeyWrapDecrypter;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\Key\SymmetricKey;
use Tuxxedo\Security\Jwt\KeyManagementAlgorithm;

class AesKeyWrapDecrypterTest extends TestCase
{
    private static function bytes(
        string $hex,
    ): string {
        /** @var string */
        return \hex2bin($hex);
    }

    public function testUnwrapKeyRecoversRfc3394VectorForA128Kw(): void
    {
        $decrypter = new AesKeyWrapDecrypter(
            algorithm: KeyManagementAlgorithm::A128KW,
            key: new SymmetricKey(
                secret: self::bytes('000102030405060708090A0B0C0D0E0F'),
            ),
        );

        self::assertSame(
            self::bytes('00112233445566778899AABBCCDDEEFF'),
            $decrypter->unwrapKey(
                wrappedKey: self::bytes('1FA68B0A8112B447AEF34BD8FB5A7B829D3E862371D2CFE5'),
            ),
        );
    }

    public function testConstructorAcceptsA192Kw(): void
    {
        self::assertInstanceOf(
            AesKeyWrapDecrypter::class,
            new AesKeyWrapDecrypter(
                algorithm: KeyManagementAlgorithm::A192KW,
                key: new SymmetricKey(
                    secret: \str_repeat("\x00", 24),
                ),
            ),
        );
    }

    public function testConstructorAcceptsA256Kw(): void
    {
        self::assertInstanceOf(
            AesKeyWrapDecrypter::class,
            new AesKeyWrapDecrypter(
                algorithm: KeyManagementAlgorithm::A256KW,
                key: new SymmetricKey(
                    secret: \str_repeat("\x00", 32),
                ),
            ),
        );
    }

    public function testConstructorThrowsForDirectAlgorithm(): void
    {
        $this->expectException(JwtException::class);

        new AesKeyWrapDecrypter(
            algorithm: KeyManagementAlgorithm::DIR,
            key: new SymmetricKey(
                secret: \str_repeat("\x00", 16),
            ),
        );
    }

    public function testConstructorThrowsForKeyLengthMismatch(): void
    {
        $this->expectException(JwtException::class);

        new AesKeyWrapDecrypter(
            algorithm: KeyManagementAlgorithm::A128KW,
            key: new SymmetricKey(
                secret: \str_repeat("\x00", 32),
            ),
        );
    }
}
