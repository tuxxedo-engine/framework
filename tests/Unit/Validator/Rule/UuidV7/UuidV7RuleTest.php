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

namespace Unit\Validator\Rule\UuidV7;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\UuidV7\UuidV7Rule;
use Tuxxedo\Validator\Rule\UuidV7\UuidV7ViolationCode;
use Tuxxedo\Validator\ViolationCodeInterface;

class UuidV7RuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'valid v7' => [
            '018f8c46-3f00-7000-8000-abcdef012345',
            null,
        ];

        yield 'v4 fails' => [
            '550e8400-e29b-41d4-a716-446655440000',
            UuidV7ViolationCode::INVALID_FORMAT,
        ];

        yield 'bad format' => [
            'not-a-uuid',
            UuidV7ViolationCode::INVALID_FORMAT,
        ];

        yield 'wrong type' => [
            42,
            CommonViolationCode::WRONG_TYPE,
        ];
    }

    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        ViolationCodeInterface|null $expected,
    ): void {
        $result = $this->runRule(
            rule: new UuidV7Rule(),
            value: $value,
        );

        if ($expected === null) {
            self::assertNull($result);

            return;
        }

        self::assertNotNull($result);
        self::assertSame($expected, $result->code);
    }
}
