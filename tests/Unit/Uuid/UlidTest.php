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

namespace Unit\Uuid;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Uuid\Ulid;
use Tuxxedo\Uuid\UuidException;

class UlidTest extends TestCase
{
    public function testGenerateReturnsCrockfordBase32(): void
    {
        $ulid = Ulid::generate();

        self::assertMatchesRegularExpression(
            '/^[0-9A-HJKMNP-TV-Z]{26}$/',
            $ulid->value,
        );
    }

    public function testGenerateProducesDistinctValues(): void
    {
        $seen = [];

        for ($i = 0; $i < 100; $i++) {
            $seen[Ulid::generate()->value] = true;
        }

        self::assertCount(100, $seen);
    }

    public function testGenerateSortsAscendingByCreationTime(): void
    {
        $values = [];

        for ($i = 0; $i < 5; $i++) {
            $values[] = Ulid::generate()->value;
            \usleep(2000);
        }

        $sorted = $values;

        \sort($sorted);

        self::assertSame($values, $sorted);
    }

    public function testConstructorNormalizesCase(): void
    {
        $ulid = new Ulid(
            value: '01arz3ndektsv4rrffq69g5fav',
        );

        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', $ulid->value);
    }

    public function testConstructorRejectsInvalidLength(): void
    {
        $this->expectException(UuidException::class);

        new Ulid(
            value: 'ABC',
        );
    }

    public function testConstructorRejectsForbiddenAlphabetChar(): void
    {
        $this->expectException(UuidException::class);

        new Ulid(
            value: '01ARZ3NDEKTSV4RRFFQ69G5FAI',
        );
    }

    public function testEqualsComparesValues(): void
    {
        $a = new Ulid(
            value: '01ARZ3NDEKTSV4RRFFQ69G5FAV',
        );
        $b = new Ulid(
            value: '01ARZ3NDEKTSV4RRFFQ69G5FAV',
        );
        $c = Ulid::generate();

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }
}
