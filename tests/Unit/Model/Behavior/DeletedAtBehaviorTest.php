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
use Tuxxedo\Model\Attribute\Column\DeletedAt;
use Tuxxedo\Model\Behavior\DeletedAtBehavior;
use Tuxxedo\Model\MetaData\ModelColumn;
use Tuxxedo\Temporal\FixedClock;
use Tuxxedo\Temporal\Instant;
use Tuxxedo\Temporal\InstantInterface;

class DeletedAtBehaviorTest extends TestCase
{
    public function testBeforeDeleteSetsClockNowIntoModelProperty(): void
    {
        $model = new class () {
            public ?InstantInterface $deletedAt = null;
        };
        $fixed = Instant::parse(
            input: '2026-07-16T10:30:45Z',
        );

        $behavior = new DeletedAtBehavior(
            clock: new FixedClock(instant: $fixed),
        );

        $behavior->beforeDelete(
            model: $model,
            column: $this->makeColumn(),
        );

        self::assertSame($fixed, $model->deletedAt);
    }

    private function makeColumn(): ModelColumn
    {
        return new ModelColumn(
            property: 'deletedAt',
            column: 'deletedAt',
            nullable: true,
            unique: false,
            readonly: false,
            attribute: new DeletedAt(),
        );
    }
}
