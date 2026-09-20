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
use Tuxxedo\Model\Attribute\Column\Uuid;
use Tuxxedo\Model\Attribute\Column\UuidVersion;
use Tuxxedo\Model\Behavior\UuidV4Behavior;
use Tuxxedo\Model\MetaData\ModelColumn;

class UuidV4BehaviorTest extends TestCase
{
    public function testAssignsGeneratedUuidWhenPropertyIsNull(): void
    {
        $model = new class () {
            public ?string $id = null;
        };

        $behavior = new UuidV4Behavior();
        $behavior->beforeInsert(
            model: $model,
            column: $this->makeColumn(),
        );

        self::assertNotNull($model->id);
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $model->id,
        );
    }

    public function testDoesNotOverwriteCallerSetValue(): void
    {
        $model = new class () {
            public ?string $id = '550e8400-e29b-41d4-a716-446655440000';
        };

        $behavior = new UuidV4Behavior();
        $behavior->beforeInsert(
            model: $model,
            column: $this->makeColumn(),
        );

        self::assertSame('550e8400-e29b-41d4-a716-446655440000', $model->id);
    }

    private function makeColumn(): ModelColumn
    {
        return new ModelColumn(
            property: 'id',
            column: 'id',
            nullable: true,
            unique: false,
            readonly: false,
            attribute: new Uuid(version: UuidVersion::V4),
        );
    }
}
