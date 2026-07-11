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
use Tuxxedo\Security\Jwt\Signer\EcdsaSigner;
use Tuxxedo\Security\Jwt\Verifier\EcdsaVerifier;

class JwkParserEcTest extends TestCase
{
    /**
     * @return array{"P-256": int, "P-384": int, "P-521": int}
     */
    private function coordinateLengths(): array
    {
        return [
            'P-256' => 32,
            'P-384' => 48,
            'P-521' => 66,
        ];
    }

    private function coordinateLength(
        string $curve,
    ): int {
        $lengths = $this->coordinateLengths();

        return $lengths[$curve];
    }

    /**
     * @return array<string, string>
     */
    private function ecPublicJwkFromFixture(
        string $curve,
        string $pem,
    ): array {
        $components = OpensslKeyComponents::ecPublic(
            pem: $pem,
        );

        $length = $this->coordinateLength(
            curve: $curve,
        );

        return [
            'kty' => 'EC',
            'crv' => $curve,
            'x' => $this->base64UrlEncode(
                bytes: $this->leftPad(
                    bytes: $components['x'],
                    length: $length,
                ),
            ),
            'y' => $this->base64UrlEncode(
                bytes: $this->leftPad(
                    bytes: $components['y'],
                    length: $length,
                ),
            ),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function ecPrivateJwkFromFixture(
        string $curve,
        string $pem,
    ): array {
        $components = OpensslKeyComponents::ecPrivate(
            pem: $pem,
        );

        $length = $this->coordinateLength(
            curve: $curve,
        );

        return [
            'kty' => 'EC',
            'crv' => $curve,
            'x' => $this->base64UrlEncode(
                bytes: $this->leftPad(
                    bytes: $components['x'],
                    length: $length,
                ),
            ),
            'y' => $this->base64UrlEncode(
                bytes: $this->leftPad(
                    bytes: $components['y'],
                    length: $length,
                ),
            ),
            'd' => $this->base64UrlEncode(
                bytes: $this->leftPad(
                    bytes: $components['d'],
                    length: $length,
                ),
            ),
        ];
    }

    private function leftPad(
        string $bytes,
        int $length,
    ): string {
        return \str_pad($bytes, $length, "\x00", \STR_PAD_LEFT);
    }

    private function base64UrlEncode(
        string $bytes,
    ): string {
        return \rtrim(
            \strtr(\base64_encode($bytes), '+/', '-_'),
            '=',
        );
    }

    public function testEcPublicPemFromP256IsAcceptedByEcdsaPublicKey(): void
    {
        $pem = JwkParser::ecPublicPemFrom(
            jwk: $this->ecPublicJwkFromFixture(
                curve: 'P-256',
                pem: JwtKeyFixtures::ecdsaP256PublicPem(),
            ),
        );

        self::assertInstanceOf(
            \OpenSSLAsymmetricKey::class,
            (new EcdsaPublicKey(
                key: $pem,
            ))->handle,
        );
    }

    public function testEcPublicPemFromP384IsAcceptedByEcdsaPublicKey(): void
    {
        $pem = JwkParser::ecPublicPemFrom(
            jwk: $this->ecPublicJwkFromFixture(
                curve: 'P-384',
                pem: JwtKeyFixtures::ecdsaP384PublicPem(),
            ),
        );

        self::assertInstanceOf(
            \OpenSSLAsymmetricKey::class,
            (new EcdsaPublicKey(
                key: $pem,
            ))->handle,
        );
    }

    public function testEcPublicPemFromP521IsAcceptedByEcdsaPublicKey(): void
    {
        $pem = JwkParser::ecPublicPemFrom(
            jwk: $this->ecPublicJwkFromFixture(
                curve: 'P-521',
                pem: JwtKeyFixtures::ecdsaP521PublicPem(),
            ),
        );

        self::assertInstanceOf(
            \OpenSSLAsymmetricKey::class,
            (new EcdsaPublicKey(
                key: $pem,
            ))->handle,
        );
    }

    public function testEcPrivatePemFromP256IsAcceptedByEcdsaPrivateKey(): void
    {
        $pem = JwkParser::ecPrivatePemFrom(
            jwk: $this->ecPrivateJwkFromFixture(
                curve: 'P-256',
                pem: JwtKeyFixtures::ecdsaP256PrivatePem(),
            ),
        );

        self::assertInstanceOf(
            \OpenSSLAsymmetricKey::class,
            (new EcdsaPrivateKey(
                key: $pem,
            ))->handle,
        );
    }

    public function testEcPrivatePemFromP384IsAcceptedByEcdsaPrivateKey(): void
    {
        $pem = JwkParser::ecPrivatePemFrom(
            jwk: $this->ecPrivateJwkFromFixture(
                curve: 'P-384',
                pem: JwtKeyFixtures::ecdsaP384PrivatePem(),
            ),
        );

        self::assertInstanceOf(
            \OpenSSLAsymmetricKey::class,
            (new EcdsaPrivateKey(
                key: $pem,
            ))->handle,
        );
    }

    public function testEcPrivatePemFromP521IsAcceptedByEcdsaPrivateKey(): void
    {
        $pem = JwkParser::ecPrivatePemFrom(
            jwk: $this->ecPrivateJwkFromFixture(
                curve: 'P-521',
                pem: JwtKeyFixtures::ecdsaP521PrivatePem(),
            ),
        );

        self::assertInstanceOf(
            \OpenSSLAsymmetricKey::class,
            (new EcdsaPrivateKey(
                key: $pem,
            ))->handle,
        );
    }

    public function testJwkDerivedEcKeysSignAndVerifyEndToEndForP256(): void
    {
        $privatePem = JwkParser::ecPrivatePemFrom(
            jwk: $this->ecPrivateJwkFromFixture(
                curve: 'P-256',
                pem: JwtKeyFixtures::ecdsaP256PrivatePem(),
            ),
        );

        $publicPem = JwkParser::ecPublicPemFrom(
            jwk: $this->ecPublicJwkFromFixture(
                curve: 'P-256',
                pem: JwtKeyFixtures::ecdsaP256PublicPem(),
            ),
        );

        $signature = (new EcdsaSigner(
            algorithm: Algorithm::ES256,
            key: new EcdsaPrivateKey(
                key: $privatePem,
            ),
        ))->sign(
            payload: 'jwk ec round-trip',
        );

        self::assertTrue(
            (new EcdsaVerifier(
                algorithm: Algorithm::ES256,
                key: new EcdsaPublicKey(
                    key: $publicPem,
                ),
            ))->verify(
                payload: 'jwk ec round-trip',
                signature: $signature,
            ),
        );
    }

    public function testJwkDerivedEcKeysSignAndVerifyEndToEndForP521(): void
    {
        $privatePem = JwkParser::ecPrivatePemFrom(
            jwk: $this->ecPrivateJwkFromFixture(
                curve: 'P-521',
                pem: JwtKeyFixtures::ecdsaP521PrivatePem(),
            ),
        );

        $publicPem = JwkParser::ecPublicPemFrom(
            jwk: $this->ecPublicJwkFromFixture(
                curve: 'P-521',
                pem: JwtKeyFixtures::ecdsaP521PublicPem(),
            ),
        );

        $signature = (new EcdsaSigner(
            algorithm: Algorithm::ES512,
            key: new EcdsaPrivateKey(
                key: $privatePem,
            ),
        ))->sign(
            payload: 'jwk ec round-trip p521',
        );

        self::assertTrue(
            (new EcdsaVerifier(
                algorithm: Algorithm::ES512,
                key: new EcdsaPublicKey(
                    key: $publicPem,
                ),
            ))->verify(
                payload: 'jwk ec round-trip p521',
                signature: $signature,
            ),
        );
    }

    public function testEcPublicPemFromThrowsWhenCurveMissing(): void
    {
        $this->expectException(JwtException::class);

        JwkParser::ecPublicPemFrom(
            jwk: [
                'kty' => 'EC',
                'x' => 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
                'y' => 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
            ],
        );
    }

    public function testEcPublicPemFromThrowsWhenCurveIsUnsupported(): void
    {
        $this->expectException(JwtException::class);

        JwkParser::ecPublicPemFrom(
            jwk: [
                'kty' => 'EC',
                'crv' => 'P-192',
                'x' => 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
                'y' => 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
            ],
        );
    }

    public function testEcPublicPemFromThrowsWhenCurveIsNotAString(): void
    {
        $this->expectException(JwtException::class);

        JwkParser::ecPublicPemFrom(
            jwk: [
                'kty' => 'EC',
                'crv' => 256,
                'x' => 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
                'y' => 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
            ],
        );
    }

    public function testEcPublicPemFromThrowsWhenXMissing(): void
    {
        $this->expectException(JwtException::class);

        JwkParser::ecPublicPemFrom(
            jwk: [
                'kty' => 'EC',
                'crv' => 'P-256',
                'y' => 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
            ],
        );
    }

    public function testEcPublicPemFromThrowsWhenYMissing(): void
    {
        $this->expectException(JwtException::class);

        JwkParser::ecPublicPemFrom(
            jwk: [
                'kty' => 'EC',
                'crv' => 'P-256',
                'x' => 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
            ],
        );
    }

    public function testEcPublicPemFromThrowsWhenCoordinateHasWrongLength(): void
    {
        $shortValue = $this->base64UrlEncode(
            bytes: \str_repeat("\x00", 16),
        );

        $this->expectException(JwtException::class);

        JwkParser::ecPublicPemFrom(
            jwk: [
                'kty' => 'EC',
                'crv' => 'P-256',
                'x' => $shortValue,
                'y' => $shortValue,
            ],
        );
    }

    public function testEcPrivatePemFromThrowsWhenDMissing(): void
    {
        $jwk = $this->ecPrivateJwkFromFixture(
            curve: 'P-256',
            pem: JwtKeyFixtures::ecdsaP256PrivatePem(),
        );

        unset($jwk['d']);

        $this->expectException(JwtException::class);

        JwkParser::ecPrivatePemFrom(
            jwk: $jwk,
        );
    }
}
