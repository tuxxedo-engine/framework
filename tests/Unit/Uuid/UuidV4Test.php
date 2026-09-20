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
use Tuxxedo\Uuid\UuidV4;

class UuidV4Test extends TestCase
{
    public function testGenerateReturnsCanonicalFormat(): void
    {
        $uuid = UuidV4::generate();

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid->value,
        );
    }

    public function testGenerateAdvertisesVersion4(): void
    {
        $uuid = UuidV4::generate();

        self::assertSame(4, $uuid->getVersion());
    }

    public function testGenerateProducesDistinctValues(): void
    {
        $seen = [];

        for ($i = 0; $i < 100; $i++) {
            $seen[UuidV4::generate()->value] = true;
        }

        self::assertCount(100, $seen);
    }
}
