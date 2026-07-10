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
use Tuxxedo\Security\Jwt\Algorithm;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\Key\EcdsaPrivateKey;
use Tuxxedo\Security\Jwt\Signer\EcdsaSigner;

class EcdsaSignerTest extends TestCase
{
    public function testSignProducesSixtyFourBytesForEs256(): void
    {
        $signer = new EcdsaSigner(
            algorithm: Algorithm::ES256,
            key: new EcdsaPrivateKey(
                key: JwtKeyFixtures::ecdsaP256PrivatePem(),
            ),
        );

        self::assertSame(
            64,
            \strlen(
                $signer->sign(
                    payload: 'hello',
                ),
            ),
        );
    }

    public function testSignProducesNinetySixBytesForEs384(): void
    {
        $signer = new EcdsaSigner(
            algorithm: Algorithm::ES384,
            key: new EcdsaPrivateKey(
                key: JwtKeyFixtures::ecdsaP384PrivatePem(),
            ),
        );

        self::assertSame(
            96,
            \strlen(
                $signer->sign(
                    payload: 'hello',
                ),
            ),
        );
    }

    public function testSignProducesOneHundredThirtyTwoBytesForEs512(): void
    {
        $signer = new EcdsaSigner(
            algorithm: Algorithm::ES512,
            key: new EcdsaPrivateKey(
                key: JwtKeyFixtures::ecdsaP521PrivatePem(),
            ),
        );

        self::assertSame(
            132,
            \strlen(
                $signer->sign(
                    payload: 'hello',
                ),
            ),
        );
    }

    public function testSignIsNonDeterministic(): void
    {
        $signer = new EcdsaSigner(
            algorithm: Algorithm::ES256,
            key: new EcdsaPrivateKey(
                key: JwtKeyFixtures::ecdsaP256PrivatePem(),
            ),
        );

        self::assertNotSame(
            $signer->sign(
                payload: 'same input',
            ),
            $signer->sign(
                payload: 'same input',
            ),
        );
    }

    public function testConstructorThrowsForNonEcdsaAlgorithm(): void
    {
        $this->expectException(JwtException::class);

        new EcdsaSigner(
            algorithm: Algorithm::HS256,
            key: new EcdsaPrivateKey(
                key: JwtKeyFixtures::ecdsaP256PrivatePem(),
            ),
        );
    }
}
