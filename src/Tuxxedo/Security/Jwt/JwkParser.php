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

namespace Tuxxedo\Security\Jwt;

use Tuxxedo\Security\Crypto\CryptoException;
use Tuxxedo\Security\Crypto\Der\DerEncoder;
use Tuxxedo\Security\Jwt\Key\EcdsaPrivateKey;
use Tuxxedo\Security\Jwt\Key\EcdsaPublicKey;
use Tuxxedo\Security\Jwt\Key\EdDsaPrivateKey;
use Tuxxedo\Security\Jwt\Key\EdDsaPublicKey;
use Tuxxedo\Security\Jwt\Key\KeyInterface;
use Tuxxedo\Security\Jwt\Key\RsaPrivateKey;
use Tuxxedo\Security\Jwt\Key\RsaPublicKey;
use Tuxxedo\Security\Jwt\Key\SymmetricKey;

class JwkParser
{
    private const string OID_RSA_ENCRYPTION = '1.2.840.113549.1.1.1';
    private const string OID_EC_PUBLIC_KEY = '1.2.840.10045.2.1';
    private const string OID_P256 = '1.2.840.10045.3.1.7';
    private const string OID_P384 = '1.3.132.0.34';
    private const string OID_P521 = '1.3.132.0.35';

    /**
     * @param array<string, mixed> $jwk
     *
     * @throws CryptoException
     * @throws JwtException
     */
    public static function parse(
        array $jwk,
    ): KeyInterface {
        if (!\array_key_exists('kty', $jwk)) {
            throw JwtException::fromMissingJwkField(
                field: 'kty',
            );
        }

        $kty = $jwk['kty'];

        if (!\is_string($kty) || $kty === '') {
            throw JwtException::fromInvalidJwkField(
                field: 'kty',
            );
        }

        $keyId = self::readOptionalKeyId(
            jwk: $jwk,
        );

        return match ($kty) {
            'oct' => self::parseSymmetric(
                jwk: $jwk,
                keyId: $keyId,
            ),
            'OKP' => self::parseOkp(
                jwk: $jwk,
                keyId: $keyId,
            ),
            'RSA' => self::parseRsa(
                jwk: $jwk,
                keyId: $keyId,
            ),
            'EC' => self::parseEc(
                jwk: $jwk,
                keyId: $keyId,
            ),
            default => throw JwtException::fromUnsupportedKeyType(
                kty: $kty,
            ),
        };
    }

    /**
     * @param array<string, mixed> $jwk
     *
     * @throws JwtException
     */
    private static function parseSymmetric(
        array $jwk,
        ?string $keyId,
    ): SymmetricKey {
        return new SymmetricKey(
            secret: self::readBase64UrlField(
                jwk: $jwk,
                field: 'k',
            ),
            keyId: $keyId,
        );
    }

    /**
     * @param array<string, mixed> $jwk
     *
     * @throws JwtException
     */
    private static function parseOkp(
        array $jwk,
        ?string $keyId,
    ): KeyInterface {
        $crv = self::readCurve(
            jwk: $jwk,
        );

        if ($crv !== 'Ed25519') {
            throw JwtException::fromUnsupportedCurve(
                crv: $crv,
            );
        }

        if (\array_key_exists('d', $jwk)) {
            $seed = self::readBase64UrlField(
                jwk: $jwk,
                field: 'd',
            );

            if (\strlen($seed) !== \SODIUM_CRYPTO_SIGN_SEEDBYTES) {
                throw JwtException::fromInvalidOkpKeyLength(
                    field: 'd',
                    expected: \SODIUM_CRYPTO_SIGN_SEEDBYTES,
                    given: \strlen($seed),
                );
            }

            /** @var non-empty-string $secretKey */
            $secretKey = \sodium_crypto_sign_secretkey(
                key_pair: \sodium_crypto_sign_seed_keypair(
                    seed: $seed,
                ),
            );

            return new EdDsaPrivateKey(
                bytes: $secretKey,
                keyId: $keyId,
            );
        }

        $public = self::readBase64UrlField(
            jwk: $jwk,
            field: 'x',
        );

        if (\strlen($public) !== \SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            throw JwtException::fromInvalidOkpKeyLength(
                field: 'x',
                expected: \SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES,
                given: \strlen($public),
            );
        }

        return new EdDsaPublicKey(
            bytes: $public,
            keyId: $keyId,
        );
    }

    /**
     * @param array<string, mixed> $jwk
     *
     * @throws CryptoException
     * @throws JwtException
     */
    private static function parseRsa(
        array $jwk,
        ?string $keyId,
    ): KeyInterface {
        if (\array_key_exists('d', $jwk)) {
            return new RsaPrivateKey(
                key: self::rsaPrivatePemFrom(
                    jwk: $jwk,
                ),
                keyId: $keyId,
            );
        }

        return new RsaPublicKey(
            key: self::rsaPublicPemFrom(
                jwk: $jwk,
            ),
            keyId: $keyId,
        );
    }

    /**
     * @param array<string, mixed> $jwk
     *
     * @throws CryptoException
     * @throws JwtException
     */
    private static function parseEc(
        array $jwk,
        ?string $keyId,
    ): KeyInterface {
        if (\array_key_exists('d', $jwk)) {
            return new EcdsaPrivateKey(
                key: self::ecPrivatePemFrom(
                    jwk: $jwk,
                ),
                keyId: $keyId,
            );
        }

        return new EcdsaPublicKey(
            key: self::ecPublicPemFrom(
                jwk: $jwk,
            ),
            keyId: $keyId,
        );
    }

    /**
     * @param array<string, mixed> $jwk
     *
     * @throws JwtException
     */
    private static function readOptionalKeyId(
        array $jwk,
    ): ?string {
        if (!\array_key_exists('kid', $jwk)) {
            return null;
        }

        $value = $jwk['kid'];

        if (!\is_string($value)) {
            throw JwtException::fromInvalidJwkField(
                field: 'kid',
            );
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $jwk
     *
     * @throws CryptoException
     * @throws JwtException
     */
    public static function rsaPublicPemFrom(
        array $jwk,
    ): string {
        $n = self::readBase64UrlField(
            jwk: $jwk,
            field: 'n',
        );

        $e = self::readBase64UrlField(
            jwk: $jwk,
            field: 'e',
        );

        $der = DerEncoder::sequence(
            DerEncoder::sequence(
                DerEncoder::objectIdentifier(
                    oid: self::OID_RSA_ENCRYPTION,
                ),
                DerEncoder::null(),
            ),
            DerEncoder::bitString(
                bytes: DerEncoder::sequence(
                    DerEncoder::integer(
                        bytes: $n,
                    ),
                    DerEncoder::integer(
                        bytes: $e,
                    ),
                ),
            ),
        );

        return self::wrapPem(
            der: $der,
            label: 'PUBLIC KEY',
        );
    }

    /**
     * @param array<string, mixed> $jwk
     *
     * @throws CryptoException
     * @throws JwtException
     */
    public static function rsaPrivatePemFrom(
        array $jwk,
    ): string {
        $decoded = [];

        foreach (['n', 'e', 'd', 'p', 'q', 'dp', 'dq', 'qi'] as $field) {
            $decoded[$field] = self::readBase64UrlField(
                jwk: $jwk,
                field: $field,
            );
        }

        $der = DerEncoder::sequence(
            DerEncoder::integer(
                bytes: "\x00",
            ),
            DerEncoder::integer(
                bytes: $decoded['n'],
            ),
            DerEncoder::integer(
                bytes: $decoded['e'],
            ),
            DerEncoder::integer(
                bytes: $decoded['d'],
            ),
            DerEncoder::integer(
                bytes: $decoded['p'],
            ),
            DerEncoder::integer(
                bytes: $decoded['q'],
            ),
            DerEncoder::integer(
                bytes: $decoded['dp'],
            ),
            DerEncoder::integer(
                bytes: $decoded['dq'],
            ),
            DerEncoder::integer(
                bytes: $decoded['qi'],
            ),
        );

        return self::wrapPem(
            der: $der,
            label: 'RSA PRIVATE KEY',
        );
    }

    /**
     * @param array<string, mixed> $jwk
     *
     * @throws CryptoException
     * @throws JwtException
     */
    public static function ecPublicPemFrom(
        array $jwk,
    ): string {
        [$curveOid, $coordinateLength] = self::curveInfo(
            crv: self::readCurve(
                jwk: $jwk,
            ),
        );

        $x = self::readEcCoordinate(
            jwk: $jwk,
            field: 'x',
            expectedLength: $coordinateLength,
        );

        $y = self::readEcCoordinate(
            jwk: $jwk,
            field: 'y',
            expectedLength: $coordinateLength,
        );

        $der = DerEncoder::sequence(
            DerEncoder::sequence(
                DerEncoder::objectIdentifier(
                    oid: self::OID_EC_PUBLIC_KEY,
                ),
                DerEncoder::objectIdentifier(
                    oid: $curveOid,
                ),
            ),
            DerEncoder::bitString(
                bytes: "\x04" . $x . $y,
            ),
        );

        return self::wrapPem(
            der: $der,
            label: 'PUBLIC KEY',
        );
    }

    /**
     * @param array<string, mixed> $jwk
     *
     * @throws CryptoException
     * @throws JwtException
     */
    public static function ecPrivatePemFrom(
        array $jwk,
    ): string {
        [$curveOid, $coordinateLength] = self::curveInfo(
            crv: self::readCurve(
                jwk: $jwk,
            ),
        );

        $d = self::readEcCoordinate(
            jwk: $jwk,
            field: 'd',
            expectedLength: $coordinateLength,
        );

        $x = self::readEcCoordinate(
            jwk: $jwk,
            field: 'x',
            expectedLength: $coordinateLength,
        );

        $y = self::readEcCoordinate(
            jwk: $jwk,
            field: 'y',
            expectedLength: $coordinateLength,
        );

        $der = DerEncoder::sequence(
            DerEncoder::integer(
                bytes: "\x01",
            ),
            DerEncoder::octetString(
                bytes: $d,
            ),
            DerEncoder::contextExplicit(
                tag: 0,
                inner: DerEncoder::objectIdentifier(
                    oid: $curveOid,
                ),
            ),
            DerEncoder::contextExplicit(
                tag: 1,
                inner: DerEncoder::bitString(
                    bytes: "\x04" . $x . $y,
                ),
            ),
        );

        return self::wrapPem(
            der: $der,
            label: 'EC PRIVATE KEY',
        );
    }

    /**
     * @return array{string, int}
     *
     * @throws JwtException
     */
    private static function curveInfo(
        string $crv,
    ): array {
        return match ($crv) {
            'P-256' => [
                self::OID_P256,
                32,
            ],
            'P-384' => [
                self::OID_P384,
                48,
            ],
            'P-521' => [
                self::OID_P521,
                66,
            ],
            default => throw JwtException::fromUnsupportedCurve(
                crv: $crv,
            ),
        };
    }

    /**
     * @param array<string, mixed> $jwk
     *
     * @throws JwtException
     */
    private static function readCurve(
        array $jwk,
    ): string {
        if (!\array_key_exists('crv', $jwk)) {
            throw JwtException::fromMissingJwkField(
                field: 'crv',
            );
        }

        $value = $jwk['crv'];

        if (!\is_string($value) || $value === '') {
            throw JwtException::fromInvalidJwkField(
                field: 'crv',
            );
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $jwk
     *
     * @throws JwtException
     */
    private static function readEcCoordinate(
        array $jwk,
        string $field,
        int $expectedLength,
    ): string {
        $bytes = self::readBase64UrlField(
            jwk: $jwk,
            field: $field,
        );

        if (\strlen($bytes) !== $expectedLength) {
            throw JwtException::fromInvalidEcCoordinate(
                field: $field,
                expected: $expectedLength,
                given: \strlen($bytes),
            );
        }

        return $bytes;
    }

    /**
     * @param array<string, mixed> $jwk
     *
     * @throws JwtException
     */
    private static function readBase64UrlField(
        array $jwk,
        string $field,
    ): string {
        if (!\array_key_exists($field, $jwk)) {
            throw JwtException::fromMissingJwkField(
                field: $field,
            );
        }

        $value = $jwk[$field];

        if (!\is_string($value) || $value === '') {
            throw JwtException::fromInvalidJwkField(
                field: $field,
            );
        }

        $normalized = \strtr($value, '-_', '+/');
        $padding = \strlen($normalized) % 4;

        if ($padding > 0) {
            $normalized .= \str_repeat('=', 4 - $padding);
        }

        $decoded = \base64_decode($normalized, strict: true);

        if ($decoded === false) {
            throw JwtException::fromInvalidJwkField(
                field: $field,
            );
        }

        return $decoded;
    }

    private static function wrapPem(
        string $der,
        string $label,
    ): string {
        $base64 = \base64_encode($der);
        $chunks = \chunk_split($base64, 64, "\n");

        return '-----BEGIN ' . $label . "-----\n" .
            $chunks .
            '-----END ' . $label . "-----\n";
    }
}
