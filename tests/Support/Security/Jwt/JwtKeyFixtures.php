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

namespace Support\Security\Jwt;

class JwtKeyFixtures
{
    private const string FIXTURE_DIR = __DIR__ . '/../../../Fixture/Security/Jwt';

    /**
     * @return non-empty-string
     */
    public static function rsaPrivatePem(): string
    {
        return self::read(
            name: 'rsa_private.pem',
        );
    }

    /**
     * @return non-empty-string
     */
    public static function rsaPublicPem(): string
    {
        return self::read(
            name: 'rsa_public.pem',
        );
    }

    /**
     * @return non-empty-string
     */
    public static function rsaOtherPrivatePem(): string
    {
        return self::read(
            name: 'rsa_other_private.pem',
        );
    }

    /**
     * @return non-empty-string
     */
    public static function rsaOtherPublicPem(): string
    {
        return self::read(
            name: 'rsa_other_public.pem',
        );
    }

    /**
     * @return non-empty-string
     */
    public static function ecdsaP256PrivatePem(): string
    {
        return self::read(
            name: 'ecdsa_p256_private.pem',
        );
    }

    /**
     * @return non-empty-string
     */
    public static function ecdsaP256PublicPem(): string
    {
        return self::read(
            name: 'ecdsa_p256_public.pem',
        );
    }

    /**
     * @return non-empty-string
     */
    public static function ecdsaP256OtherPrivatePem(): string
    {
        return self::read(
            name: 'ecdsa_p256_other_private.pem',
        );
    }

    /**
     * @return non-empty-string
     */
    public static function ecdsaP256OtherPublicPem(): string
    {
        return self::read(
            name: 'ecdsa_p256_other_public.pem',
        );
    }

    /**
     * @return non-empty-string
     */
    public static function ecdsaP384PrivatePem(): string
    {
        return self::read(
            name: 'ecdsa_p384_private.pem',
        );
    }

    /**
     * @return non-empty-string
     */
    public static function ecdsaP384PublicPem(): string
    {
        return self::read(
            name: 'ecdsa_p384_public.pem',
        );
    }

    /**
     * @return non-empty-string
     */
    public static function ecdsaP521PrivatePem(): string
    {
        return self::read(
            name: 'ecdsa_p521_private.pem',
        );
    }

    /**
     * @return non-empty-string
     */
    public static function ecdsaP521PublicPem(): string
    {
        return self::read(
            name: 'ecdsa_p521_public.pem',
        );
    }

    /**
     * @return non-empty-string
     */
    public static function eddsaPrivateBytes(): string
    {
        return self::read(
            name: 'eddsa_private.bin',
        );
    }

    /**
     * @return non-empty-string
     */
    public static function eddsaPublicBytes(): string
    {
        return self::read(
            name: 'eddsa_public.bin',
        );
    }

    /**
     * @return non-empty-string
     */
    public static function eddsaOtherPrivateBytes(): string
    {
        return self::read(
            name: 'eddsa_other_private.bin',
        );
    }

    /**
     * @return non-empty-string
     */
    public static function eddsaOtherPublicBytes(): string
    {
        return self::read(
            name: 'eddsa_other_public.bin',
        );
    }

    /**
     * @return non-empty-string
     */
    public static function hmacSecretBytes(): string
    {
        return self::read(
            name: 'hmac_secret.bin',
        );
    }

    /**
     * @return non-empty-string
     */
    private static function read(
        string $name,
    ): string {
        $path = self::FIXTURE_DIR . '/' . $name;
        $contents = \file_get_contents($path);

        if ($contents === false || $contents === '') {
            throw new \RuntimeException(
                \sprintf(
                    'JWT fixture missing, unreadable, or empty: %s',
                    $path,
                ),
            );
        }

        return $contents;
    }
}
