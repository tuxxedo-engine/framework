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

namespace Unit\Model\Attribute\Relation;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Model\Attribute\Relation\RelationKeyTupleHasher;

class RelationKeyTupleHasherTest extends TestCase
{
    public function testHashJoinsScalarsWithUnitSeparator(): void
    {
        self::assertSame(
            "system\x1Falpha",
            RelationKeyTupleHasher::hash(values: [
                'system',
                'alpha',
            ]),
        );
    }

    public function testHashRendersIntegerValuesAsStrings(): void
    {
        self::assertSame(
            "7\x1F42",
            RelationKeyTupleHasher::hash(values: [
                7,
                42,
            ]),
        );
    }

    public function testHashRendersBooleanValuesAsOneOrZero(): void
    {
        self::assertSame(
            "1\x1F0",
            RelationKeyTupleHasher::hash(values: [
                true,
                false,
            ]),
        );
    }
}
