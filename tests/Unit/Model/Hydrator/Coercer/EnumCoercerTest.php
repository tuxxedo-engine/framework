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

namespace Unit\Model\Hydrator\Coercer;

use Fixture\Model\PostStatus;
use Fixture\Model\Priority;
use Fixture\Model\PublishState;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Model\Hydrator\Coercer\EnumCoercer;
use Tuxxedo\Model\ModelException;

class EnumCoercerTest extends TestCase
{
    public function testHydrateBackedStringEnumFromStringValue(): void
    {
        $coercer = new EnumCoercer(enum: PostStatus::class);

        self::assertSame(
            PostStatus::PUBLISHED,
            $coercer->hydrate(value: 'published'),
        );
    }

    public function testDehydrateBackedStringEnumReturnsValue(): void
    {
        $coercer = new EnumCoercer(enum: PostStatus::class);

        self::assertSame(
            'published',
            $coercer->dehydrate(value: PostStatus::PUBLISHED),
        );
    }

    public function testHydrateBackedIntEnumFromIntValue(): void
    {
        $coercer = new EnumCoercer(enum: Priority::class);

        self::assertSame(
            Priority::HIGH,
            $coercer->hydrate(value: 3),
        );
    }

    public function testDehydrateBackedIntEnumReturnsIntValue(): void
    {
        $coercer = new EnumCoercer(enum: Priority::class);

        self::assertSame(
            3,
            $coercer->dehydrate(value: Priority::HIGH),
        );
    }

    public function testHydrateUnitEnumFromCaseName(): void
    {
        $coercer = new EnumCoercer(enum: PublishState::class);

        self::assertSame(
            PublishState::PUBLISHED,
            $coercer->hydrate(value: 'PUBLISHED'),
        );
    }

    public function testDehydrateUnitEnumReturnsCaseName(): void
    {
        $coercer = new EnumCoercer(enum: PublishState::class);

        self::assertSame(
            'PUBLISHED',
            $coercer->dehydrate(value: PublishState::PUBLISHED),
        );
    }

    public function testHydrateBackedEnumWithUnknownValueThrows(): void
    {
        $coercer = new EnumCoercer(enum: PostStatus::class);

        $this->expectException(ModelException::class);

        $coercer->hydrate(value: 'not-a-status');
    }

    public function testHydrateUnitEnumWithUnknownCaseThrows(): void
    {
        $coercer = new EnumCoercer(enum: PublishState::class);

        $this->expectException(ModelException::class);

        $coercer->hydrate(value: 'NONSENSE');
    }

    /**
     * @return \Generator<array{0: float|bool}>
     */
    public static function backedEnumInvalidHydrateTypeDataProvider(): \Generator
    {
        yield [
            1.5,
        ];

        yield [
            true,
        ];

        yield [
            false,
        ];
    }

    #[DataProvider('backedEnumInvalidHydrateTypeDataProvider')]
    public function testHydrateBackedEnumWithInvalidTypeThrows(
        float|bool $value,
    ): void {
        $coercer = new EnumCoercer(enum: PostStatus::class);

        $this->expectException(ModelException::class);

        $coercer->hydrate(value: $value);
    }

    /**
     * @return \Generator<array{0: int|float|bool}>
     */
    public static function unitEnumInvalidHydrateTypeDataProvider(): \Generator
    {
        yield [
            1,
        ];

        yield [
            1.5,
        ];

        yield [
            true,
        ];
    }

    #[DataProvider('unitEnumInvalidHydrateTypeDataProvider')]
    public function testHydrateUnitEnumWithInvalidTypeThrows(
        int|float|bool $value,
    ): void {
        $coercer = new EnumCoercer(enum: PublishState::class);

        $this->expectException(ModelException::class);

        $coercer->hydrate(value: $value);
    }

    public function testDehydrateNonEnumThrows(): void
    {
        $coercer = new EnumCoercer(enum: PostStatus::class);

        $this->expectException(ModelException::class);

        $coercer->dehydrate(value: 'raw string');
    }
}
