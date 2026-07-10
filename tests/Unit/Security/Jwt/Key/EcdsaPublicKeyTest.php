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

namespace Unit\Security\Jwt\Key;

use PHPUnit\Framework\TestCase;
use Support\Security\Jwt\JwtKeyFixtures;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\Key\EcdsaPublicKey;

class EcdsaPublicKeyTest extends TestCase
{
    public function testParsesP256Pem(): void
    {
        $key = new EcdsaPublicKey(
            key: JwtKeyFixtures::ecdsaP256PublicPem(),
        );

        self::assertInstanceOf(
            \OpenSSLAsymmetricKey::class,
            $key->handle,
        );
    }

    public function testParsesP384Pem(): void
    {
        $key = new EcdsaPublicKey(
            key: JwtKeyFixtures::ecdsaP384PublicPem(),
        );

        self::assertInstanceOf(
            \OpenSSLAsymmetricKey::class,
            $key->handle,
        );
    }

    public function testParsesP521Pem(): void
    {
        $key = new EcdsaPublicKey(
            key: JwtKeyFixtures::ecdsaP521PublicPem(),
        );

        self::assertInstanceOf(
            \OpenSSLAsymmetricKey::class,
            $key->handle,
        );
    }

    public function testStoresKeyIdWhenProvided(): void
    {
        $key = new EcdsaPublicKey(
            key: JwtKeyFixtures::ecdsaP256PublicPem(),
            keyId: 'ec-p256',
        );

        self::assertSame('ec-p256', $key->keyId);
    }

    public function testThrowsForInvalidPem(): void
    {
        $this->expectException(JwtException::class);

        new EcdsaPublicKey(
            key: 'not a pem',
        );
    }
}
