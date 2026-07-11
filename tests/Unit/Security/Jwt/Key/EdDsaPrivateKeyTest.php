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
use Tuxxedo\Security\Jwt\Key\EdDsaPrivateKey;
use Tuxxedo\Security\Jwt\Key\EdDsaPublicKey;
use Tuxxedo\Security\Jwt\Signer\EdDsaSigner;
use Tuxxedo\Security\Jwt\Verifier\EdDsaVerifier;

class EdDsaPrivateKeyTest extends TestCase
{
    public function testAcceptsCorrectLengthBytes(): void
    {
        $bytes = JwtKeyFixtures::eddsaPrivateBytes();

        $key = new EdDsaPrivateKey(
            bytes: $bytes,
        );

        self::assertSame($bytes, $key->bytes);
    }

    public function testStoresKeyIdWhenProvided(): void
    {
        $key = new EdDsaPrivateKey(
            bytes: JwtKeyFixtures::eddsaPrivateBytes(),
            keyId: 'ed25519-priv',
        );

        self::assertSame('ed25519-priv', $key->keyId);
    }

    public function testThrowsForBytesShorterThanRequired(): void
    {
        /** @var non-empty-string $tooShort */
        $tooShort = \str_repeat("\x00", \SODIUM_CRYPTO_SIGN_SECRETKEYBYTES - 1);

        $this->expectException(JwtException::class);

        new EdDsaPrivateKey(
            bytes: $tooShort,
        );
    }

    public function testThrowsForBytesLongerThanRequired(): void
    {
        /** @var non-empty-string $tooLong */
        $tooLong = \str_repeat("\x00", \SODIUM_CRYPTO_SIGN_SECRETKEYBYTES + 1);

        $this->expectException(JwtException::class);

        new EdDsaPrivateKey(
            bytes: $tooLong,
        );
    }

    public function testRejectsPublicKeyLengthBytes(): void
    {
        $this->expectException(JwtException::class);

        new EdDsaPrivateKey(
            bytes: JwtKeyFixtures::eddsaPublicBytes(),
        );
    }

    public function testToPublicYieldsEdDsaPublicKey(): void
    {
        $private = new EdDsaPrivateKey(
            bytes: JwtKeyFixtures::eddsaPrivateBytes(),
        );

        self::assertInstanceOf(
            EdDsaPublicKey::class,
            $private->toPublic(),
        );
    }

    public function testToPublicPreservesKeyId(): void
    {
        $private = new EdDsaPrivateKey(
            bytes: JwtKeyFixtures::eddsaPrivateBytes(),
            keyId: 'ed25519-priv',
        );

        self::assertSame(
            'ed25519-priv',
            $private->toPublic()->keyId,
        );
    }

    public function testToPublicMatchesCanonicalPublicBytes(): void
    {
        $private = new EdDsaPrivateKey(
            bytes: JwtKeyFixtures::eddsaPrivateBytes(),
        );

        self::assertSame(
            JwtKeyFixtures::eddsaPublicBytes(),
            $private->toPublic()->bytes,
        );
    }

    public function testToPublicYieldsKeyThatVerifiesRoundTripSignature(): void
    {
        $private = new EdDsaPrivateKey(
            bytes: JwtKeyFixtures::eddsaPrivateBytes(),
        );

        $signer = new EdDsaSigner(
            key: $private,
        );

        $signature = $signer->sign(
            payload: 'round-trip payload',
        );

        $verifier = new EdDsaVerifier(
            key: $private->toPublic(),
        );

        self::assertTrue(
            $verifier->verify(
                payload: 'round-trip payload',
                signature: $signature,
            ),
        );
    }
}
