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

namespace Unit\Security\Jwt\Signer;

use PHPUnit\Framework\TestCase;
use Support\Security\Jwt\JwtKeyFixtures;
use Tuxxedo\Security\Jwt\Key\EdDsaPrivateKey;
use Tuxxedo\Security\Jwt\Signer\EdDsaSigner;

class EdDsaSignerTest extends TestCase
{
    private function makeKey(): EdDsaPrivateKey
    {
        return new EdDsaPrivateKey(
            bytes: JwtKeyFixtures::eddsaPrivateBytes(),
        );
    }

    public function testSignProducesSixtyFourBytes(): void
    {
        $signer = new EdDsaSigner(
            key: $this->makeKey(),
        );

        self::assertSame(
            \SODIUM_CRYPTO_SIGN_BYTES,
            \strlen(
                $signer->sign(
                    payload: 'hello',
                ),
            ),
        );
    }

    public function testSignIsDeterministic(): void
    {
        $signer = new EdDsaSigner(
            key: $this->makeKey(),
        );

        self::assertSame(
            $signer->sign(
                payload: 'same input',
            ),
            $signer->sign(
                payload: 'same input',
            ),
        );
    }

    public function testDifferentKeysProduceDifferentSignatures(): void
    {
        $primary = new EdDsaSigner(
            key: new EdDsaPrivateKey(
                bytes: JwtKeyFixtures::eddsaPrivateBytes(),
            ),
        );

        $other = new EdDsaSigner(
            key: new EdDsaPrivateKey(
                bytes: JwtKeyFixtures::eddsaOtherPrivateBytes(),
            ),
        );

        self::assertNotSame(
            $primary->sign(
                payload: 'shared payload',
            ),
            $other->sign(
                payload: 'shared payload',
            ),
        );
    }

    public function testDifferentPayloadsProduceDifferentSignatures(): void
    {
        $signer = new EdDsaSigner(
            key: $this->makeKey(),
        );

        self::assertNotSame(
            $signer->sign(
                payload: 'one',
            ),
            $signer->sign(
                payload: 'two',
            ),
        );
    }
}
