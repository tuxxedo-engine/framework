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
use Tuxxedo\Security\Jwt\Key\RsaPrivateKey;
use Tuxxedo\Security\Jwt\Key\RsaPublicKey;
use Tuxxedo\Security\Jwt\Signer\RsaSigner;
use Tuxxedo\Security\Jwt\Verifier\RsaVerifier;

class RsaVerifierTest extends TestCase
{
    private function signWith(
        Algorithm $algorithm,
        string $payload,
    ): string {
        return (new RsaSigner(
            algorithm: $algorithm,
            key: new RsaPrivateKey(
                key: JwtKeyFixtures::rsaPrivatePem(),
            ),
        ))->sign(
            payload: $payload,
        );
    }

    public function testVerifyAcceptsSignatureWithPublicKey(): void
    {
        $payload = 'the payload';

        $verifier = new RsaVerifier(
            algorithm: Algorithm::RS256,
            key: new RsaPublicKey(
                key: JwtKeyFixtures::rsaPublicPem(),
            ),
        );

        self::assertTrue(
            $verifier->verify(
                payload: $payload,
                signature: $this->signWith(
                    algorithm: Algorithm::RS256,
                    payload: $payload,
                ),
            ),
        );
    }

    public function testVerifyAcceptsRs384AndRs512(): void
    {
        $verifier384 = new RsaVerifier(
            algorithm: Algorithm::RS384,
            key: new RsaPublicKey(
                key: JwtKeyFixtures::rsaPublicPem(),
            ),
        );

        $verifier512 = new RsaVerifier(
            algorithm: Algorithm::RS512,
            key: new RsaPublicKey(
                key: JwtKeyFixtures::rsaPublicPem(),
            ),
        );

        self::assertTrue(
            $verifier384->verify(
                payload: 'x',
                signature: $this->signWith(
                    algorithm: Algorithm::RS384,
                    payload: 'x',
                ),
            ),
        );

        self::assertTrue(
            $verifier512->verify(
                payload: 'x',
                signature: $this->signWith(
                    algorithm: Algorithm::RS512,
                    payload: 'x',
                ),
            ),
        );
    }

    public function testVerifyRejectsTamperedSignature(): void
    {
        $payload = 'the payload';
        $signature = $this->signWith(
            algorithm: Algorithm::RS256,
            payload: $payload,
        );

        $tampered = $signature;
        $tampered[0] = $tampered[0] === "\x00"
            ? "\x01"
            : "\x00";

        $verifier = new RsaVerifier(
            algorithm: Algorithm::RS256,
            key: new RsaPublicKey(
                key: JwtKeyFixtures::rsaPublicPem(),
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
        $signature = $this->signWith(
            algorithm: Algorithm::RS256,
            payload: 'original',
        );

        $verifier = new RsaVerifier(
            algorithm: Algorithm::RS256,
            key: new RsaPublicKey(
                key: JwtKeyFixtures::rsaPublicPem(),
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
        $foreignSignature = (new RsaSigner(
            algorithm: Algorithm::RS256,
            key: new RsaPrivateKey(
                key: JwtKeyFixtures::rsaOtherPrivatePem(),
            ),
        ))->sign(
            payload: $payload,
        );

        $verifier = new RsaVerifier(
            algorithm: Algorithm::RS256,
            key: new RsaPublicKey(
                key: JwtKeyFixtures::rsaPublicPem(),
            ),
        );

        self::assertFalse(
            $verifier->verify(
                payload: $payload,
                signature: $foreignSignature,
            ),
        );
    }

    public function testConstructorThrowsForNonRsaAlgorithm(): void
    {
        $this->expectException(JwtException::class);

        new RsaVerifier(
            algorithm: Algorithm::HS256,
            key: new RsaPublicKey(
                key: JwtKeyFixtures::rsaPublicPem(),
            ),
        );
    }
}
