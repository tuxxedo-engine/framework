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
use Tuxxedo\Model\Attribute\Column\UpdatedAt;
use Tuxxedo\Model\Behavior\UpdatedAtBehavior;
use Tuxxedo\Model\MetaData\ModelColumn;
use Tuxxedo\Temporal\AdvancingClock;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\FixedClock;
use Tuxxedo\Temporal\Instant;
use Tuxxedo\Temporal\InstantInterface;

class UpdatedAtBehaviorTest extends TestCase
{
    public function testBeforeInsertSetsClockNowIntoModelProperty(): void
    {
        $model = new class () {
            public ?InstantInterface $updatedAt = null;
        };
        $fixed = Instant::parse(
            input: '2026-07-16T10:30:45Z',
        );

        $behavior = new UpdatedAtBehavior(
            clock: new FixedClock(instant: $fixed),
        );

        $behavior->beforeInsert(
            model: $model,
            column: $this->makeColumn(),
        );

        self::assertSame($fixed, $model->updatedAt);
    }

    public function testBeforeUpdateBumpsPropertyFromClock(): void
    {
        $model = new class () {
            public ?InstantInterface $updatedAt = null;
        };
        $behavior = new UpdatedAtBehavior(
            clock: new AdvancingClock(
                start: Instant::parse(input: '2026-07-16T10:00:00Z'),
                step: Duration::fromMinutes(minutes: 1),
            ),
        );

        $behavior->beforeInsert(
            model: $model,
            column: $this->makeColumn(),
        );
        $insertedAt = $model->updatedAt;

        $behavior->beforeUpdate(
            model: $model,
            column: $this->makeColumn(),
        );

        self::assertNotNull($insertedAt);
        self::assertNotNull($model->updatedAt);
        self::assertSame(60, $model->updatedAt->toUnixTimestamp() - $insertedAt->toUnixTimestamp());
    }

    private function makeColumn(): ModelColumn
    {
        return new ModelColumn(
            property: 'updatedAt',
            column: 'updatedAt',
            nullable: true,
            unique: false,
            readonly: false,
            attribute: new UpdatedAt(),
        );
    }
}
