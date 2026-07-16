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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Model\Hydrator\Coercer\JsonCoercer;
use Tuxxedo\Model\ModelException;

class JsonCoercerTest extends TestCase
{
    public function testHydrateAssocArray(): void
    {
        $coercer = new JsonCoercer();

        self::assertSame(
            [
                'name' => 'Alice',
                'age' => 30,
            ],
            $coercer->hydrate(value: '{"name":"Alice","age":30}'),
        );
    }

    public function testDehydrateAssocArray(): void
    {
        $coercer = new JsonCoercer();

        self::assertSame(
            '{"name":"Alice","age":30}',
            $coercer->dehydrate(
                value: [
                    'name' => 'Alice',
                    'age' => 30,
                ],
            ),
        );
    }

    public function testRoundTripNestedArray(): void
    {
        $coercer = new JsonCoercer();

        $original = [
            'user' => [
                'name' => 'Alice',
                'roles' => [
                    'admin',
                    'editor',
                ],
            ],
            'active' => true,
            'score' => 42.5,
            'notes' => null,
        ];

        $encoded = $coercer->dehydrate(value: $original);

        /** @var string $encoded */
        $decoded = $coercer->hydrate(value: $encoded);

        self::assertSame(
            $original,
            $decoded,
        );
    }

    public function testHydrateReturnsScalarForNonObjectJson(): void
    {
        $coercer = new JsonCoercer();

        self::assertSame(
            42,
            $coercer->hydrate(value: '42'),
        );
    }

    public function testHydrateReturnsNullForJsonNull(): void
    {
        $coercer = new JsonCoercer();

        self::assertNull(
            $coercer->hydrate(value: 'null'),
        );
    }

    /**
     * @return \Generator<array{0: int|float|bool}>
     */
    public static function nonStringHydrateInputDataProvider(): \Generator
    {
        yield [
            42,
        ];

        yield [
            3.14,
        ];

        yield [
            true,
        ];

        yield [
            false,
        ];
    }

    #[DataProvider('nonStringHydrateInputDataProvider')]
    public function testHydrateNonStringInputThrows(
        int|float|bool $value,
    ): void {
        $this->expectException(ModelException::class);

        (new JsonCoercer())->hydrate(value: $value);
    }

    public function testHydrateMalformedJsonThrows(): void
    {
        $this->expectException(\JsonException::class);

        (new JsonCoercer())->hydrate(value: '{not: valid json');
    }

    public function testDehydrateUnencodableValueThrows(): void
    {
        $this->expectException(\JsonException::class);

        (new JsonCoercer())->dehydrate(value: \NAN);
    }
}
