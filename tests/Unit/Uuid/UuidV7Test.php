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
use Tuxxedo\Uuid\UuidV7;

class UuidV7Test extends TestCase
{
    public function testGenerateReturnsCanonicalFormat(): void
    {
        $uuid = UuidV7::generate();

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid->value,
        );
    }

    public function testGenerateAdvertisesVersion7(): void
    {
        $uuid = UuidV7::generate();

        self::assertSame(7, $uuid->getVersion());
    }

    public function testGenerateProducesDistinctValues(): void
    {
        $seen = [];

        for ($i = 0; $i < 100; $i++) {
            $seen[UuidV7::generate()->value] = true;
        }

        self::assertCount(100, $seen);
    }

    public function testGenerateSortsAscendingByCreationTime(): void
    {
        $values = [];

        for ($i = 0; $i < 5; $i++) {
            $values[] = UuidV7::generate()->value;
            \usleep(2000);
        }

        $sorted = $values;

        \sort($sorted);

        self::assertSame($values, $sorted);
    }
}
