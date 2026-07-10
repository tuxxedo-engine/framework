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
use Tuxxedo\Security\Jwt\Key\SymmetricKey;

class SymmetricKeyTest extends TestCase
{
    public function testStoresSecretAndDefaultsKeyIdToNull(): void
    {
        $key = new SymmetricKey(
            secret: 'shhh',
        );

        self::assertSame('shhh', $key->secret);
        self::assertNull($key->keyId);
    }

    public function testStoresKeyIdWhenProvided(): void
    {
        $key = new SymmetricKey(
            secret: 'shhh',
            keyId: 'primary',
        );

        self::assertSame('primary', $key->keyId);
    }

    public function testAcceptsEmptyStringAsSecret(): void
    {
        $key = new SymmetricKey(
            secret: '',
        );

        self::assertSame('', $key->secret);
    }
}
