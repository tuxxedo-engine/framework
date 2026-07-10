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
use Tuxxedo\Security\Jwt\Key\SymmetricKey;
use Tuxxedo\Security\Jwt\Signer\HmacSigner;
use Tuxxedo\Security\Jwt\Verifier\HmacVerifier;

class HmacVerifierTest extends TestCase
{
    private function makeKey(): SymmetricKey
    {
        return new SymmetricKey(
            secret: JwtKeyFixtures::hmacSecretBytes(),
        );
    }

    public function testVerifyAcceptsSignatureFromPairedSigner(): void
    {
        $payload = 'the payload';
        $signature = (new HmacSigner(
            algorithm: Algorithm::HS256,
            key: $this->makeKey(),
        ))->sign(
            payload: $payload,
        );

        $verifier = new HmacVerifier(
            algorithm: Algorithm::HS256,
            key: $this->makeKey(),
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
        $payload = 'the payload';
        $signature = (new HmacSigner(
            algorithm: Algorithm::HS256,
            key: $this->makeKey(),
        ))->sign(
            payload: $payload,
        );

        $tampered = $signature;
        $tampered[0] = $tampered[0] === "\x00"
            ? "\x01"
            : "\x00";

        $verifier = new HmacVerifier(
            algorithm: Algorithm::HS256,
            key: $this->makeKey(),
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
        $signature = (new HmacSigner(
            algorithm: Algorithm::HS256,
            key: $this->makeKey(),
        ))->sign(
            payload: 'original',
        );

        $verifier = new HmacVerifier(
            algorithm: Algorithm::HS256,
            key: $this->makeKey(),
        );

        self::assertFalse(
            $verifier->verify(
                payload: 'different',
                signature: $signature,
            ),
        );
    }

    public function testVerifyRejectsSignatureFromDifferentSecret(): void
    {
        $signature = (new HmacSigner(
            algorithm: Algorithm::HS256,
            key: new SymmetricKey(
                secret: 'other-secret',
            ),
        ))->sign(
            payload: 'payload',
        );

        $verifier = new HmacVerifier(
            algorithm: Algorithm::HS256,
            key: $this->makeKey(),
        );

        self::assertFalse(
            $verifier->verify(
                payload: 'payload',
                signature: $signature,
            ),
        );
    }

    public function testVerifyRejectsSignatureFromDifferentHmacAlgorithm(): void
    {
        $signature = (new HmacSigner(
            algorithm: Algorithm::HS512,
            key: $this->makeKey(),
        ))->sign(
            payload: 'payload',
        );

        $verifier = new HmacVerifier(
            algorithm: Algorithm::HS256,
            key: $this->makeKey(),
        );

        self::assertFalse(
            $verifier->verify(
                payload: 'payload',
                signature: $signature,
            ),
        );
    }

    public function testConstructorThrowsForNonHmacAlgorithm(): void
    {
        $this->expectException(JwtException::class);

        new HmacVerifier(
            algorithm: Algorithm::RS256,
            key: $this->makeKey(),
        );
    }
}
