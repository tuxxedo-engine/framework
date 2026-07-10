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

namespace Unit\Security\Jwt\Key;

use PHPUnit\Framework\TestCase;
use Support\Security\Jwt\JwtKeyFixtures;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\Key\EdDsaPublicKey;

class EdDsaPublicKeyTest extends TestCase
{
    public function testAcceptsCorrectLengthBytes(): void
    {
        $bytes = JwtKeyFixtures::eddsaPublicBytes();

        $key = new EdDsaPublicKey(
            bytes: $bytes,
        );

        self::assertSame($bytes, $key->bytes);
    }

    public function testStoresKeyIdWhenProvided(): void
    {
        $key = new EdDsaPublicKey(
            bytes: JwtKeyFixtures::eddsaPublicBytes(),
            keyId: 'ed25519-key',
        );

        self::assertSame('ed25519-key', $key->keyId);
    }

    public function testThrowsForBytesShorterThanRequired(): void
    {
        /** @var non-empty-string $tooShort */
        $tooShort = \str_repeat("\x00", \SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES - 1);

        $this->expectException(JwtException::class);

        new EdDsaPublicKey(
            bytes: $tooShort,
        );
    }

    public function testThrowsForBytesLongerThanRequired(): void
    {
        /** @var non-empty-string $tooLong */
        $tooLong = \str_repeat("\x00", \SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES + 1);

        $this->expectException(JwtException::class);

        new EdDsaPublicKey(
            bytes: $tooLong,
        );
    }
}
