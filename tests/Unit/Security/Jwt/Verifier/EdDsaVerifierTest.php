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
use Tuxxedo\Security\Jwt\Key\EdDsaPrivateKey;
use Tuxxedo\Security\Jwt\Key\EdDsaPublicKey;
use Tuxxedo\Security\Jwt\Signer\EdDsaSigner;
use Tuxxedo\Security\Jwt\Verifier\EdDsaVerifier;

class EdDsaVerifierTest extends TestCase
{
    private function sign(
        string $payload,
    ): string {
        return (new EdDsaSigner(
            key: new EdDsaPrivateKey(
                bytes: JwtKeyFixtures::eddsaPrivateBytes(),
            ),
        ))->sign(
            payload: $payload,
        );
    }

    public function testVerifyAcceptsRoundtripSignatureWithPublicKey(): void
    {
        $payload = 'the payload';

        $verifier = new EdDsaVerifier(
            key: new EdDsaPublicKey(
                bytes: JwtKeyFixtures::eddsaPublicBytes(),
            ),
        );

        self::assertTrue(
            $verifier->verify(
                payload: $payload,
                signature: $this->sign(
                    payload: $payload,
                ),
            ),
        );
    }

    public function testVerifyReturnsFalseForEmptySignature(): void
    {
        $verifier = new EdDsaVerifier(
            key: new EdDsaPublicKey(
                bytes: JwtKeyFixtures::eddsaPublicBytes(),
            ),
        );

        self::assertFalse(
            $verifier->verify(
                payload: 'x',
                signature: '',
            ),
        );
    }

    public function testVerifyRejectsTamperedSignature(): void
    {
        $payload = 'the payload';
        $signature = $this->sign(
            payload: $payload,
        );

        $tampered = $signature;
        $tampered[0] = $tampered[0] === "\x00"
            ? "\x01"
            : "\x00";

        $verifier = new EdDsaVerifier(
            key: new EdDsaPublicKey(
                bytes: JwtKeyFixtures::eddsaPublicBytes(),
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
        $signature = $this->sign(
            payload: 'original',
        );

        $verifier = new EdDsaVerifier(
            key: new EdDsaPublicKey(
                bytes: JwtKeyFixtures::eddsaPublicBytes(),
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
        $foreignSignature = (new EdDsaSigner(
            key: new EdDsaPrivateKey(
                bytes: JwtKeyFixtures::eddsaOtherPrivateBytes(),
            ),
        ))->sign(
            payload: $payload,
        );

        $verifier = new EdDsaVerifier(
            key: new EdDsaPublicKey(
                bytes: JwtKeyFixtures::eddsaPublicBytes(),
            ),
        );

        self::assertFalse(
            $verifier->verify(
                payload: $payload,
                signature: $foreignSignature,
            ),
        );
    }
}
