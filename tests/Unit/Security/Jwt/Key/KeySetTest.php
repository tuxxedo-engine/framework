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
use Tuxxedo\Security\Jwt\Key\KeySet;
use Tuxxedo\Security\Jwt\Key\SymmetricKey;

class KeySetTest extends TestCase
{
    public function testEmptySetFindReturnsNull(): void
    {
        self::assertNull(
            (new KeySet())->find(
                keyId: 'anything',
            ),
        );
    }

    public function testFindReturnsMatchingKey(): void
    {
        $primary = new SymmetricKey(
            secret: 'a',
            keyId: 'primary',
        );

        $secondary = new SymmetricKey(
            secret: 'b',
            keyId: 'secondary',
        );

        $set = new KeySet(
            $primary,
            $secondary,
        );

        self::assertSame(
            $secondary,
            $set->find(
                keyId: 'secondary',
            ),
        );
    }

    public function testFindReturnsNullForUnknownKeyId(): void
    {
        $set = new KeySet(
            new SymmetricKey(
                secret: 'a',
                keyId: 'primary',
            ),
        );

        self::assertNull(
            $set->find(
                keyId: 'nope',
            ),
        );
    }

    public function testFindSkipsKeysWithoutKeyId(): void
    {
        $withId = new SymmetricKey(
            secret: 'a',
            keyId: 'primary',
        );

        $set = new KeySet(
            new SymmetricKey(
                secret: 'b',
            ),
            $withId,
        );

        self::assertSame(
            $withId,
            $set->find(
                keyId: 'primary',
            ),
        );
    }

    public function testKeysAreExposedAsList(): void
    {
        $a = new SymmetricKey(
            secret: 'a',
            keyId: 'one',
        );

        $b = new SymmetricKey(
            secret: 'b',
            keyId: 'two',
        );

        $set = new KeySet(
            $a,
            $b,
        );

        self::assertSame(
            [$a, $b],
            $set->keys,
        );
    }

    public function testFindReturnsFirstMatchOnDuplicateKeyIds(): void
    {
        $first = new SymmetricKey(
            secret: 'a',
            keyId: 'dup',
        );

        $second = new SymmetricKey(
            secret: 'b',
            keyId: 'dup',
        );

        $set = new KeySet(
            $first,
            $second,
        );

        self::assertSame(
            $first,
            $set->find(
                keyId: 'dup',
            ),
        );
    }
}
