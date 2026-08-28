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

namespace Unit\Security\Jwt\Encrypter;

use PHPUnit\Framework\TestCase;
use Support\Security\Jwt\JwtKeyFixtures;
use Tuxxedo\Security\Jwt\Encrypter\DirectEncrypter;
use Tuxxedo\Security\Jwt\Encrypter\EncrypterFactory;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\Key\EdDsaPublicKey;
use Tuxxedo\Security\Jwt\Key\SymmetricKey;

class DirectEncrypterTest extends TestCase
{
    public function testWrapKeyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            (new DirectEncrypter())->wrapKey(
                contentEncryptionKey: \str_repeat("\x00", 32),
            ),
        );
    }

    public function testWrapKeyReturnsEmptyStringRegardlessOfInput(): void
    {
        self::assertSame(
            '',
            (new DirectEncrypter())->wrapKey(
                contentEncryptionKey: '',
            ),
        );
    }

    public function testFactoryCreateDirectReturnsDirectEncrypterForSymmetricKey(): void
    {
        self::assertInstanceOf(
            DirectEncrypter::class,
            EncrypterFactory::createDirect(
                key: new SymmetricKey(
                    secret: \str_repeat("\x00", 32),
                ),
            ),
        );
    }

    public function testFactoryCreateDirectThrowsForNonSymmetricKey(): void
    {
        $this->expectException(JwtException::class);

        EncrypterFactory::createDirect(
            key: new EdDsaPublicKey(
                bytes: JwtKeyFixtures::eddsaPublicBytes(),
            ),
        );
    }
}
