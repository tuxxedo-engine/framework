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
use Tuxxedo\Security\Jwt\Encrypter\DirectEncrypter;

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
}
