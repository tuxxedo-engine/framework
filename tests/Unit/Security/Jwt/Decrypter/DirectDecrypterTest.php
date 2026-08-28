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

namespace Unit\Security\Jwt\Decrypter;

use PHPUnit\Framework\TestCase;
use Support\Security\Jwt\JwtKeyFixtures;
use Tuxxedo\Security\Jwt\Decrypter\DecrypterFactory;
use Tuxxedo\Security\Jwt\Decrypter\DirectDecrypter;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\Key\EdDsaPublicKey;
use Tuxxedo\Security\Jwt\Key\SymmetricKey;

class DirectDecrypterTest extends TestCase
{
    public function testUnwrapKeyReturnsKeySecret(): void
    {
        $secret = \str_repeat("\x42", 32);

        self::assertSame(
            $secret,
            (new DirectDecrypter(
                key: new SymmetricKey(
                    secret: $secret,
                ),
            ))->unwrapKey(
                wrappedKey: '',
            ),
        );
    }

    public function testUnwrapKeyIgnoresInputArgument(): void
    {
        $secret = \str_repeat("\x77", 16);

        self::assertSame(
            $secret,
            (new DirectDecrypter(
                key: new SymmetricKey(
                    secret: $secret,
                ),
            ))->unwrapKey(
                wrappedKey: 'anything at all',
            ),
        );
    }

    public function testFactoryCreateDirectReturnsDirectDecrypterForSymmetricKey(): void
    {
        $secret = \str_repeat("\x11", 32);

        $decrypter = DecrypterFactory::createDirect(
            key: new SymmetricKey(
                secret: $secret,
            ),
        );

        self::assertSame(
            $secret,
            $decrypter->unwrapKey(
                wrappedKey: '',
            ),
        );
    }

    public function testFactoryCreateDirectThrowsForNonSymmetricKey(): void
    {
        $this->expectException(JwtException::class);

        DecrypterFactory::createDirect(
            key: new EdDsaPublicKey(
                bytes: JwtKeyFixtures::eddsaPublicBytes(),
            ),
        );
    }
}
