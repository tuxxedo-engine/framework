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
use Tuxxedo\Security\Jwt\Key\EcdsaPrivateKey;
use Tuxxedo\Security\Jwt\Key\EcdsaPublicKey;
use Tuxxedo\Security\Jwt\Key\EdDsaPrivateKey;
use Tuxxedo\Security\Jwt\Key\EdDsaPublicKey;
use Tuxxedo\Security\Jwt\Key\RsaPrivateKey;
use Tuxxedo\Security\Jwt\Key\RsaPublicKey;
use Tuxxedo\Security\Jwt\Key\SymmetricKey;
use Tuxxedo\Security\Jwt\Signer\EdDsaSigner;
use Tuxxedo\Security\Jwt\Signer\HmacSigner;
use Tuxxedo\Security\Jwt\Verifier\EdDsaVerifier;
use Tuxxedo\Security\Jwt\Verifier\HmacVerifier;

class JwkParserTest extends TestCase
{
    private function base64UrlEncode(
        string $bytes,
    ): string {
        return \rtrim(
            \strtr(\base64_encode($bytes), '+/', '-_'),
            '=',
        );
    }

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
    private function ecPublicJwkFromFixture(): array
    {
        $components = OpensslKeyComponents::ecPublic(
            pem: JwtKeyFixtures::ecdsaP256PublicPem(),
        );

        return [
            'kty' => 'EC',
            'crv' => 'P-256',
            'x' => $this->base64UrlEncode(
                bytes: \str_pad($components['x'], 32, "\x00", \STR_PAD_LEFT),
            ),
            'y' => $this->base64UrlEncode(
                bytes: \str_pad($components['y'], 32, "\x00", \STR_PAD_LEFT),
            ),
        ];
    }

    public function testParseOctReturnsSymmetricKey(): void
    {
        $key = JwkParser::parse(
            jwk: [
                'kty' => 'oct',
                'k' => $this->base64UrlEncode(
                    bytes: JwtKeyFixtures::hmacSecretBytes(),
                ),
            ],
        );

        self::assertInstanceOf(
            SymmetricKey::class,
            $key,
        );

        self::assertSame(
            JwtKeyFixtures::hmacSecretBytes(),
            $key->secret,
        );
    }

    public function testParseOctPropagatesKidToKeyId(): void
    {
        $key = JwkParser::parse(
            jwk: [
                'kty' => 'oct',
                'kid' => 'shared-2026',
                'k' => $this->base64UrlEncode(
                    bytes: JwtKeyFixtures::hmacSecretBytes(),
                ),
            ],
        );

        self::assertSame(
            'shared-2026',
            $key->keyId,
        );
    }

    public function testParseOctLeavesKeyIdNullWhenKidMissing(): void
    {
        $key = JwkParser::parse(
            jwk: [
                'kty' => 'oct',
                'k' => $this->base64UrlEncode(
                    bytes: JwtKeyFixtures::hmacSecretBytes(),
                ),
            ],
        );

        self::assertNull(
            $key->keyId,
        );
    }

    public function testParseOctThrowsWhenSecretMissing(): void
    {
        $this->expectException(JwtException::class);

        JwkParser::parse(
            jwk: [
                'kty' => 'oct',
            ],
        );
    }

    public function testParseOctSignAndVerifyEndToEnd(): void
    {
        $key = JwkParser::parse(
            jwk: [
                'kty' => 'oct',
                'k' => $this->base64UrlEncode(
                    bytes: JwtKeyFixtures::hmacSecretBytes(),
                ),
            ],
        );

        self::assertInstanceOf(
            SymmetricKey::class,
            $key,
        );

        $signature = (new HmacSigner(
            algorithm: Algorithm::HS256,
            key: $key,
        ))->sign(
            payload: 'oct-jwk-payload',
        );

        self::assertTrue(
            (new HmacVerifier(
                algorithm: Algorithm::HS256,
                key: $key,
            ))->verify(
                payload: 'oct-jwk-payload',
                signature: $signature,
            ),
        );
    }

    public function testParseOkpEd25519PublicReturnsEdDsaPublicKey(): void
    {
        $key = JwkParser::parse(
            jwk: [
                'kty' => 'OKP',
                'crv' => 'Ed25519',
                'x' => $this->base64UrlEncode(
                    bytes: JwtKeyFixtures::eddsaPublicBytes(),
                ),
            ],
        );

        self::assertInstanceOf(
            EdDsaPublicKey::class,
            $key,
        );

        self::assertSame(
            JwtKeyFixtures::eddsaPublicBytes(),
            $key->bytes,
        );
    }

    public function testParseOkpEd25519PrivateReturnsEdDsaPrivateKey(): void
    {
        $seed = \substr(
            JwtKeyFixtures::eddsaPrivateBytes(),
            0,
            \SODIUM_CRYPTO_SIGN_SEEDBYTES,
        );

        $key = JwkParser::parse(
            jwk: [
                'kty' => 'OKP',
                'crv' => 'Ed25519',
                'x' => $this->base64UrlEncode(
                    bytes: JwtKeyFixtures::eddsaPublicBytes(),
                ),
                'd' => $this->base64UrlEncode(
                    bytes: $seed,
                ),
            ],
        );

        self::assertInstanceOf(
            EdDsaPrivateKey::class,
            $key,
        );

        self::assertSame(
            JwtKeyFixtures::eddsaPrivateBytes(),
            $key->bytes,
        );
    }

    public function testParseOkpEd25519PrivateSignsAndVerifiesEndToEnd(): void
    {
        $seed = \substr(
            JwtKeyFixtures::eddsaPrivateBytes(),
            0,
            \SODIUM_CRYPTO_SIGN_SEEDBYTES,
        );

        $privateKey = JwkParser::parse(
            jwk: [
                'kty' => 'OKP',
                'crv' => 'Ed25519',
                'x' => $this->base64UrlEncode(
                    bytes: JwtKeyFixtures::eddsaPublicBytes(),
                ),
                'd' => $this->base64UrlEncode(
                    bytes: $seed,
                ),
            ],
        );

        $publicKey = JwkParser::parse(
            jwk: [
                'kty' => 'OKP',
                'crv' => 'Ed25519',
                'x' => $this->base64UrlEncode(
                    bytes: JwtKeyFixtures::eddsaPublicBytes(),
                ),
            ],
        );

        self::assertInstanceOf(
            EdDsaPrivateKey::class,
            $privateKey,
        );

        self::assertInstanceOf(
            EdDsaPublicKey::class,
            $publicKey,
        );

        $signature = (new EdDsaSigner(
            key: $privateKey,
        ))->sign(
            payload: 'okp-jwk-payload',
        );

        self::assertTrue(
            (new EdDsaVerifier(
                key: $publicKey,
            ))->verify(
                payload: 'okp-jwk-payload',
                signature: $signature,
            ),
        );
    }

    public function testParseOkpThrowsForNonEd25519Curve(): void
    {
        $this->expectException(JwtException::class);

        JwkParser::parse(
            jwk: [
                'kty' => 'OKP',
                'crv' => 'X25519',
                'x' => $this->base64UrlEncode(
                    bytes: JwtKeyFixtures::eddsaPublicBytes(),
                ),
            ],
        );
    }

    public function testParseOkpThrowsForWrongPublicKeyLength(): void
    {
        $this->expectException(JwtException::class);

        JwkParser::parse(
            jwk: [
                'kty' => 'OKP',
                'crv' => 'Ed25519',
                'x' => $this->base64UrlEncode(
                    bytes: \str_repeat("\x00", 16),
                ),
            ],
        );
    }

    public function testParseOkpThrowsForWrongSeedLength(): void
    {
        $this->expectException(JwtException::class);

        JwkParser::parse(
            jwk: [
                'kty' => 'OKP',
                'crv' => 'Ed25519',
                'x' => $this->base64UrlEncode(
                    bytes: JwtKeyFixtures::eddsaPublicBytes(),
                ),
                'd' => $this->base64UrlEncode(
                    bytes: \str_repeat("\x00", 16),
                ),
            ],
        );
    }

    public function testParseRsaPublicReturnsRsaPublicKey(): void
    {
        $key = JwkParser::parse(
            jwk: $this->rsaPublicJwkFromFixture(),
        );

        self::assertInstanceOf(
            RsaPublicKey::class,
            $key,
        );
    }

    public function testParseRsaPropagatesKidToKeyId(): void
    {
        $jwk = $this->rsaPublicJwkFromFixture();
        $jwk['kid'] = 'rsa-issuer-2026';

        $key = JwkParser::parse(
            jwk: $jwk,
        );

        self::assertSame(
            'rsa-issuer-2026',
            $key->keyId,
        );
    }

    public function testParseRsaPrivateReturnsRsaPrivateKey(): void
    {
        $components = OpensslKeyComponents::rsaPrivate(
            pem: JwtKeyFixtures::rsaPrivatePem(),
        );

        $jwk = [
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

        self::assertInstanceOf(
            RsaPrivateKey::class,
            JwkParser::parse(
                jwk: $jwk,
            ),
        );
    }

    public function testParseEcPublicReturnsEcdsaPublicKey(): void
    {
        $key = JwkParser::parse(
            jwk: $this->ecPublicJwkFromFixture(),
        );

        self::assertInstanceOf(
            EcdsaPublicKey::class,
            $key,
        );
    }

    public function testParseEcPrivateReturnsEcdsaPrivateKey(): void
    {
        $components = OpensslKeyComponents::ecPrivate(
            pem: JwtKeyFixtures::ecdsaP256PrivatePem(),
        );

        $jwk = [
            'kty' => 'EC',
            'crv' => 'P-256',
            'x' => $this->base64UrlEncode(
                bytes: \str_pad($components['x'], 32, "\x00", \STR_PAD_LEFT),
            ),
            'y' => $this->base64UrlEncode(
                bytes: \str_pad($components['y'], 32, "\x00", \STR_PAD_LEFT),
            ),
            'd' => $this->base64UrlEncode(
                bytes: \str_pad($components['d'], 32, "\x00", \STR_PAD_LEFT),
            ),
        ];

        self::assertInstanceOf(
            EcdsaPrivateKey::class,
            JwkParser::parse(
                jwk: $jwk,
            ),
        );
    }

    public function testParseThrowsWhenKtyMissing(): void
    {
        $this->expectException(JwtException::class);

        JwkParser::parse(
            jwk: [
                'k' => 'AAAA',
            ],
        );
    }

    public function testParseThrowsWhenKtyIsEmptyString(): void
    {
        $this->expectException(JwtException::class);

        JwkParser::parse(
            jwk: [
                'kty' => '',
                'k' => 'AAAA',
            ],
        );
    }

    public function testParseThrowsWhenKtyIsNotAString(): void
    {
        $this->expectException(JwtException::class);

        JwkParser::parse(
            jwk: [
                'kty' => 42,
                'k' => 'AAAA',
            ],
        );
    }

    public function testParseThrowsForUnknownKty(): void
    {
        $this->expectException(JwtException::class);

        JwkParser::parse(
            jwk: [
                'kty' => 'PGP',
                'k' => 'AAAA',
            ],
        );
    }

    public function testParseThrowsWhenKidIsNotAString(): void
    {
        $this->expectException(JwtException::class);

        JwkParser::parse(
            jwk: [
                'kty' => 'oct',
                'kid' => 42,
                'k' => $this->base64UrlEncode(
                    bytes: JwtKeyFixtures::hmacSecretBytes(),
                ),
            ],
        );
    }

    public function testParseSilentlyIgnoresUnknownJwkFields(): void
    {
        $key = JwkParser::parse(
            jwk: [
                'kty' => 'oct',
                'kid' => 'issuer-2026',
                'k' => $this->base64UrlEncode(
                    bytes: JwtKeyFixtures::hmacSecretBytes(),
                ),
                'alg' => 'HS256',
                'use' => 'sig',
                'key_ops' => [
                    'verify',
                    'sign',
                ],
                'x5t' => 'ignored-thumbprint',
                'ext' => true,
                'custom-tenant-field' => 'anything-goes',
            ],
        );

        self::assertInstanceOf(
            SymmetricKey::class,
            $key,
        );

        self::assertSame(
            JwtKeyFixtures::hmacSecretBytes(),
            $key->secret,
        );

        self::assertSame(
            'issuer-2026',
            $key->keyId,
        );
    }
}
