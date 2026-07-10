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

use PHPUnit\Framework\TestCase;
use Support\Security\Jwt\JwtKeyFixtures;
use Tuxxedo\Security\Jwt\OpensslSignature;

class OpensslSignatureTest extends TestCase
{
    private function loadPrivate(
        string $pem,
    ): \OpenSSLAsymmetricKey {
        $key = \openssl_pkey_get_private($pem);

        if ($key === false) {
            self::fail('Failed to parse private key fixture');
        }

        return $key;
    }

    private function loadPublic(
        string $pem,
    ): \OpenSSLAsymmetricKey {
        $key = \openssl_pkey_get_public($pem);

        if ($key === false) {
            self::fail('Failed to parse public key fixture');
        }

        return $key;
    }

    public function testSignReturnsNonEmptyBytesForRsa(): void
    {
        $signature = OpensslSignature::sign(
            privateKey: $this->loadPrivate(
                pem: JwtKeyFixtures::rsaPrivatePem(),
            ),
            opensslAlgorithm: \OPENSSL_ALGO_SHA256,
            payload: 'hello world',
            algorithmIdentifier: 'RS256',
        );

        self::assertNotSame('', $signature);
    }

    public function testSignAndVerifyRoundTripWithRsa(): void
    {
        $payload = 'the quick brown fox';

        $signature = OpensslSignature::sign(
            privateKey: $this->loadPrivate(
                pem: JwtKeyFixtures::rsaPrivatePem(),
            ),
            opensslAlgorithm: \OPENSSL_ALGO_SHA256,
            payload: $payload,
            algorithmIdentifier: 'RS256',
        );

        self::assertTrue(
            OpensslSignature::verify(
                publicKey: $this->loadPublic(
                    pem: JwtKeyFixtures::rsaPublicPem(),
                ),
                opensslAlgorithm: \OPENSSL_ALGO_SHA256,
                payload: $payload,
                signature: $signature,
                algorithmIdentifier: 'RS256',
            ),
        );
    }

    public function testVerifyReturnsFalseForTamperedSignature(): void
    {
        $payload = 'important payload';

        $signature = OpensslSignature::sign(
            privateKey: $this->loadPrivate(
                pem: JwtKeyFixtures::rsaPrivatePem(),
            ),
            opensslAlgorithm: \OPENSSL_ALGO_SHA256,
            payload: $payload,
            algorithmIdentifier: 'RS256',
        );

        $tampered = $signature;
        $tampered[0] = $tampered[0] === "\x00"
            ? "\x01"
            : "\x00";

        self::assertFalse(
            OpensslSignature::verify(
                publicKey: $this->loadPublic(
                    pem: JwtKeyFixtures::rsaPublicPem(),
                ),
                opensslAlgorithm: \OPENSSL_ALGO_SHA256,
                payload: $payload,
                signature: $tampered,
                algorithmIdentifier: 'RS256',
            ),
        );
    }

    public function testVerifyReturnsFalseWhenPayloadDoesNotMatch(): void
    {
        $signature = OpensslSignature::sign(
            privateKey: $this->loadPrivate(
                pem: JwtKeyFixtures::rsaPrivatePem(),
            ),
            opensslAlgorithm: \OPENSSL_ALGO_SHA256,
            payload: 'original',
            algorithmIdentifier: 'RS256',
        );

        self::assertFalse(
            OpensslSignature::verify(
                publicKey: $this->loadPublic(
                    pem: JwtKeyFixtures::rsaPublicPem(),
                ),
                opensslAlgorithm: \OPENSSL_ALGO_SHA256,
                payload: 'different',
                signature: $signature,
                algorithmIdentifier: 'RS256',
            ),
        );
    }

    public function testVerifyReturnsFalseForSignatureFromDifferentKey(): void
    {
        $payload = 'shared payload';

        $signature = OpensslSignature::sign(
            privateKey: $this->loadPrivate(
                pem: JwtKeyFixtures::rsaOtherPrivatePem(),
            ),
            opensslAlgorithm: \OPENSSL_ALGO_SHA256,
            payload: $payload,
            algorithmIdentifier: 'RS256',
        );

        self::assertFalse(
            OpensslSignature::verify(
                publicKey: $this->loadPublic(
                    pem: JwtKeyFixtures::rsaPublicPem(),
                ),
                opensslAlgorithm: \OPENSSL_ALGO_SHA256,
                payload: $payload,
                signature: $signature,
                algorithmIdentifier: 'RS256',
            ),
        );
    }

    public function testSignAndVerifyRoundTripWithEcdsa(): void
    {
        $payload = 'ecdsa payload';

        $signature = OpensslSignature::sign(
            privateKey: $this->loadPrivate(
                pem: JwtKeyFixtures::ecdsaP256PrivatePem(),
            ),
            opensslAlgorithm: \OPENSSL_ALGO_SHA256,
            payload: $payload,
            algorithmIdentifier: 'ES256',
        );

        self::assertTrue(
            OpensslSignature::verify(
                publicKey: $this->loadPublic(
                    pem: JwtKeyFixtures::ecdsaP256PublicPem(),
                ),
                opensslAlgorithm: \OPENSSL_ALGO_SHA256,
                payload: $payload,
                signature: $signature,
                algorithmIdentifier: 'ES256',
            ),
        );
    }

}
