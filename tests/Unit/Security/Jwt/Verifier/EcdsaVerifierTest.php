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

namespace Unit\Security\Jwt\Verifier;

use PHPUnit\Framework\TestCase;
use Support\Security\Jwt\JwtKeyFixtures;
use Tuxxedo\Security\Jwt\Algorithm;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\Key\EcdsaPrivateKey;
use Tuxxedo\Security\Jwt\Key\EcdsaPublicKey;
use Tuxxedo\Security\Jwt\Signer\EcdsaSigner;
use Tuxxedo\Security\Jwt\Verifier\EcdsaVerifier;

class EcdsaVerifierTest extends TestCase
{
    private function signP256(
        Algorithm $algorithm,
        string $payload,
    ): string {
        return (new EcdsaSigner(
            algorithm: $algorithm,
            key: new EcdsaPrivateKey(
                key: JwtKeyFixtures::ecdsaP256PrivatePem(),
            ),
        ))->sign(
            payload: $payload,
        );
    }

    public function testVerifyAcceptsRoundtripSignatureWithPublicKey(): void
    {
        $payload = 'the payload';

        $verifier = new EcdsaVerifier(
            algorithm: Algorithm::ES256,
            key: new EcdsaPublicKey(
                key: JwtKeyFixtures::ecdsaP256PublicPem(),
            ),
        );

        self::assertTrue(
            $verifier->verify(
                payload: $payload,
                signature: $this->signP256(
                    algorithm: Algorithm::ES256,
                    payload: $payload,
                ),
            ),
        );
    }

    public function testVerifyRoundtripForEs384(): void
    {
        $payload = 'p384';
        $signature = (new EcdsaSigner(
            algorithm: Algorithm::ES384,
            key: new EcdsaPrivateKey(
                key: JwtKeyFixtures::ecdsaP384PrivatePem(),
            ),
        ))->sign(
            payload: $payload,
        );

        $verifier = new EcdsaVerifier(
            algorithm: Algorithm::ES384,
            key: new EcdsaPublicKey(
                key: JwtKeyFixtures::ecdsaP384PublicPem(),
            ),
        );

        self::assertTrue(
            $verifier->verify(
                payload: $payload,
                signature: $signature,
            ),
        );
    }

    public function testVerifyRoundtripForEs512(): void
    {
        $payload = 'p521';
        $signature = (new EcdsaSigner(
            algorithm: Algorithm::ES512,
            key: new EcdsaPrivateKey(
                key: JwtKeyFixtures::ecdsaP521PrivatePem(),
            ),
        ))->sign(
            payload: $payload,
        );

        $verifier = new EcdsaVerifier(
            algorithm: Algorithm::ES512,
            key: new EcdsaPublicKey(
                key: JwtKeyFixtures::ecdsaP521PublicPem(),
            ),
        );

        self::assertTrue(
            $verifier->verify(
                payload: $payload,
                signature: $signature,
            ),
        );
    }

    public function testVerifyRejectsTamperedSignature(): void
    {
        $payload = 'x';
        $signature = $this->signP256(
            algorithm: Algorithm::ES256,
            payload: $payload,
        );

        $tampered = $signature;
        $tampered[0] = $tampered[0] === "\x00"
            ? "\x01"
            : "\x00";

        $verifier = new EcdsaVerifier(
            algorithm: Algorithm::ES256,
            key: new EcdsaPublicKey(
                key: JwtKeyFixtures::ecdsaP256PublicPem(),
            ),
        );

        self::assertFalse(
            $verifier->verify(
                payload: $payload,
                signature: $tampered,
            ),
        );
    }

    public function testVerifyRejectsWrongPayload(): void
    {
        $signature = $this->signP256(
            algorithm: Algorithm::ES256,
            payload: 'original',
        );

        $verifier = new EcdsaVerifier(
            algorithm: Algorithm::ES256,
            key: new EcdsaPublicKey(
                key: JwtKeyFixtures::ecdsaP256PublicPem(),
            ),
        );

        self::assertFalse(
            $verifier->verify(
                payload: 'different',
                signature: $signature,
            ),
        );
    }

    public function testVerifyRejectsSignatureFromDifferentKey(): void
    {
        $payload = 'shared';
        $foreignSignature = (new EcdsaSigner(
            algorithm: Algorithm::ES256,
            key: new EcdsaPrivateKey(
                key: JwtKeyFixtures::ecdsaP256OtherPrivatePem(),
            ),
        ))->sign(
            payload: $payload,
        );

        $verifier = new EcdsaVerifier(
            algorithm: Algorithm::ES256,
            key: new EcdsaPublicKey(
                key: JwtKeyFixtures::ecdsaP256PublicPem(),
            ),
        );

        self::assertFalse(
            $verifier->verify(
                payload: $payload,
                signature: $foreignSignature,
            ),
        );
    }

    public function testVerifyThrowsForWrongSignatureLength(): void
    {
        $verifier = new EcdsaVerifier(
            algorithm: Algorithm::ES256,
            key: new EcdsaPublicKey(
                key: JwtKeyFixtures::ecdsaP256PublicPem(),
            ),
        );

        $this->expectException(JwtException::class);

        $verifier->verify(
            payload: 'x',
            signature: \str_repeat("\x00", 63),
        );
    }

    public function testConstructorThrowsForNonEcdsaAlgorithm(): void
    {
        $this->expectException(JwtException::class);

        new EcdsaVerifier(
            algorithm: Algorithm::HS256,
            key: new EcdsaPublicKey(
                key: JwtKeyFixtures::ecdsaP256PublicPem(),
            ),
        );
    }
}
