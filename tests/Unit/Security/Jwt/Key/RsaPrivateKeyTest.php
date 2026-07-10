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
use Tuxxedo\Security\Jwt\Key\RsaPrivateKey;

class RsaPrivateKeyTest extends TestCase
{
    public function testParsesPemString(): void
    {
        $key = new RsaPrivateKey(
            key: JwtKeyFixtures::rsaPrivatePem(),
        );

        self::assertInstanceOf(
            \OpenSSLAsymmetricKey::class,
            $key->handle,
        );
    }

    public function testAcceptsPreParsedHandle(): void
    {
        $handle = \openssl_pkey_get_private(JwtKeyFixtures::rsaPrivatePem());

        if ($handle === false) {
            self::fail('Fixture PEM should have parsed');
        }

        $key = new RsaPrivateKey(
            key: $handle,
        );

        self::assertSame(
            $handle,
            $key->handle,
        );
    }

    public function testStoresKeyIdWhenProvided(): void
    {
        $key = new RsaPrivateKey(
            key: JwtKeyFixtures::rsaPrivatePem(),
            keyId: 'rsa-priv-2026',
        );

        self::assertSame('rsa-priv-2026', $key->keyId);
    }

    public function testThrowsForInvalidPem(): void
    {
        $this->expectException(JwtException::class);

        new RsaPrivateKey(
            key: 'not a pem',
        );
    }
}
