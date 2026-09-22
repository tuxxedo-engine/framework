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

namespace Unit\Model\Behavior;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Model\Attribute\Column\CreatedAt;
use Tuxxedo\Model\Behavior\CreatedAtBehavior;
use Tuxxedo\Model\MetaData\ModelColumn;
use Tuxxedo\Temporal\FixedClock;
use Tuxxedo\Temporal\Instant;
use Tuxxedo\Temporal\InstantInterface;

class CreatedAtBehaviorTest extends TestCase
{
    public function testBeforeInsertSetsClockNowIntoModelProperty(): void
    {
        $model = new class () {
            public ?InstantInterface $createdAt = null;
        };
        $fixed = Instant::parse(
            input: '2026-07-16T10:30:45Z',
        );

        $behavior = new CreatedAtBehavior(
            clock: new FixedClock(instant: $fixed),
        );

        $behavior->beforeInsert(
            model: $model,
            column: $this->makeColumn(),
        );

        self::assertSame($fixed, $model->createdAt);
    }

    private function makeColumn(): ModelColumn
    {
        return new ModelColumn(
            property: 'createdAt',
            column: 'createdAt',
            nullable: true,
            unique: false,
            readonly: false,
            attribute: new CreatedAt(),
        );
    }
}
