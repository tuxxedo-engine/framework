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
use Support\Security\Jwt\OpensslKeyComponents;
use Tuxxedo\Security\Jwt\Algorithm;
use Tuxxedo\Security\Jwt\JwkParser;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\Key\RsaPrivateKey;
use Tuxxedo\Security\Jwt\Key\RsaPublicKey;
use Tuxxedo\Security\Jwt\Signer\RsaSigner;
use Tuxxedo\Security\Jwt\Verifier\RsaVerifier;

class JwkParserRsaTest extends TestCase
{
    /**
     * @return array<string, string>
     */
    private function rsaPublicJwkFromFixture(): array
    {
        $components = OpensslKeyComponents::rsaPublic(
            pem: JwtKeyFixtures::rsaPublicPem(),
        );

        return [
            'kty' => 'RSA',
            'n' => $this->base64UrlEncode(
                bytes: $components['n'],
            ),
            'e' => $this->base64UrlEncode(
                bytes: $components['e'],
            ),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function rsaPrivateJwkFromFixture(): array
    {
        $components = OpensslKeyComponents::rsaPrivate(
            pem: JwtKeyFixtures::rsaPrivatePem(),
        );

        return [
            'kty' => 'RSA',
            'n' => $this->base64UrlEncode(
                bytes: $components['n'],
            ),
            'e' => $this->base64UrlEncode(
                bytes: $components['e'],
            ),
            'd' => $this->base64UrlEncode(
                bytes: $components['d'],
            ),
            'p' => $this->base64UrlEncode(
                bytes: $components['p'],
            ),
            'q' => $this->base64UrlEncode(
                bytes: $components['q'],
            ),
            'dp' => $this->base64UrlEncode(
                bytes: $components['dmp1'],
            ),
            'dq' => $this->base64UrlEncode(
                bytes: $components['dmq1'],
            ),
            'qi' => $this->base64UrlEncode(
                bytes: $components['iqmp'],
            ),
        ];
    }

    private function base64UrlEncode(
        string $bytes,
    ): string {
        return \rtrim(
            \strtr(\base64_encode($bytes), '+/', '-_'),
            '=',
        );
    }

    public function testRsaPublicPemFromProducesPemThatOpensslAccepts(): void
    {
        $pem = JwkParser::rsaPublicPemFrom(
            jwk: $this->rsaPublicJwkFromFixture(),
        );

        self::assertNotFalse(
            \openssl_pkey_get_public($pem),
        );
    }

    public function testRsaPublicPemFromMatchesOriginalComponents(): void
    {
        $originalJwk = $this->rsaPublicJwkFromFixture();
        $pem = JwkParser::rsaPublicPemFrom(
            jwk: $originalJwk,
        );

        $components = OpensslKeyComponents::rsaPublic(
            pem: $pem,
        );

        self::assertSame(
            $originalJwk['n'],
            $this->base64UrlEncode(
                bytes: $components['n'],
            ),
        );

        self::assertSame(
            $originalJwk['e'],
            $this->base64UrlEncode(
                bytes: $components['e'],
            ),
        );
    }

    public function testRsaPublicPemFromWorksWithRsaPublicKey(): void
    {
        $pem = JwkParser::rsaPublicPemFrom(
            jwk: $this->rsaPublicJwkFromFixture(),
        );

        $key = new RsaPublicKey(
            key: $pem,
        );

        self::assertInstanceOf(
            \OpenSSLAsymmetricKey::class,
            $key->handle,
        );
    }

    public function testRsaPublicPemFromThrowsWhenModulusMissing(): void
    {
        $this->expectException(JwtException::class);

        JwkParser::rsaPublicPemFrom(
            jwk: [
                'kty' => 'RSA',
                'e' => 'AQAB',
            ],
        );
    }

    public function testRsaPublicPemFromThrowsWhenExponentMissing(): void
    {
        $this->expectException(JwtException::class);

        JwkParser::rsaPublicPemFrom(
            jwk: [
                'kty' => 'RSA',
                'n' => 'AQAB',
            ],
        );
    }

    public function testRsaPublicPemFromThrowsWhenFieldIsNotAString(): void
    {
        $this->expectException(JwtException::class);

        JwkParser::rsaPublicPemFrom(
            jwk: [
                'kty' => 'RSA',
                'n' => 123,
                'e' => 'AQAB',
            ],
        );
    }

    public function testRsaPublicPemFromThrowsWhenFieldIsEmptyString(): void
    {
        $this->expectException(JwtException::class);

        JwkParser::rsaPublicPemFrom(
            jwk: [
                'kty' => 'RSA',
                'n' => '',
                'e' => 'AQAB',
            ],
        );
    }

    public function testRsaPublicPemFromThrowsWhenFieldIsInvalidBase64Url(): void
    {
        $this->expectException(JwtException::class);

        JwkParser::rsaPublicPemFrom(
            jwk: [
                'kty' => 'RSA',
                'n' => '!!!not-valid!!!',
                'e' => 'AQAB',
            ],
        );
    }

    public function testRsaPrivatePemFromProducesPemThatOpensslAccepts(): void
    {
        $pem = JwkParser::rsaPrivatePemFrom(
            jwk: $this->rsaPrivateJwkFromFixture(),
        );

        self::assertNotFalse(
            \openssl_pkey_get_private($pem),
        );
    }

    public function testRsaPrivatePemFromWorksWithRsaPrivateKey(): void
    {
        $pem = JwkParser::rsaPrivatePemFrom(
            jwk: $this->rsaPrivateJwkFromFixture(),
        );

        $key = new RsaPrivateKey(
            key: $pem,
        );

        self::assertInstanceOf(
            \OpenSSLAsymmetricKey::class,
            $key->handle,
        );
    }

    public function testRsaPrivatePemFromThrowsWhenPrivateExponentMissing(): void
    {
        $jwk = $this->rsaPrivateJwkFromFixture();
        unset($jwk['d']);

        $this->expectException(JwtException::class);

        JwkParser::rsaPrivatePemFrom(
            jwk: $jwk,
        );
    }

    public function testRsaPrivatePemFromThrowsWhenPrimeMissing(): void
    {
        $jwk = $this->rsaPrivateJwkFromFixture();

        unset($jwk['p']);

        $this->expectException(JwtException::class);

        JwkParser::rsaPrivatePemFrom(
            jwk: $jwk,
        );
    }

    public function testRsaPrivatePemFromThrowsWhenCoefficientMissing(): void
    {
        $jwk = $this->rsaPrivateJwkFromFixture();

        unset($jwk['qi']);

        $this->expectException(JwtException::class);

        JwkParser::rsaPrivatePemFrom(
            jwk: $jwk,
        );
    }

    public function testJwkDerivedKeysSignAndVerifyEndToEnd(): void
    {
        $privatePem = JwkParser::rsaPrivatePemFrom(
            jwk: $this->rsaPrivateJwkFromFixture(),
        );

        $publicPem = JwkParser::rsaPublicPemFrom(
            jwk: $this->rsaPublicJwkFromFixture(),
        );

        $signer = new RsaSigner(
            algorithm: Algorithm::RS256,
            key: new RsaPrivateKey(
                key: $privatePem,
            ),
        );

        $verifier = new RsaVerifier(
            algorithm: Algorithm::RS256,
            key: new RsaPublicKey(
                key: $publicPem,
            ),
        );

        $signature = $signer->sign(
            payload: 'jwk round-trip payload',
        );

        self::assertTrue(
            $verifier->verify(
                payload: 'jwk round-trip payload',
                signature: $signature,
            ),
        );
    }
}
