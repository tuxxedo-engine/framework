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

namespace Unit\Validator\Rule\Uuid;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\Uuid\UuidRule;
use Tuxxedo\Validator\Rule\Uuid\UuidViolationCode;
use Tuxxedo\Validator\ViolationCodeInterface;

class UuidRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'v4 uuid' => [
            '550e8400-e29b-41d4-a716-446655440000',
            null,
        ];

        yield 'v7 uuid' => [
            '018f8c46-3f00-7000-8000-abcdef012345',
            null,
        ];

        yield 'uppercase uuid' => [
            '550E8400-E29B-41D4-A716-446655440000',
            null,
        ];

        yield 'missing dashes' => [
            '550e8400e29b41d4a716446655440000',
            UuidViolationCode::INVALID_FORMAT,
        ];

        yield 'wrong length' => [
            '550e8400-e29b-41d4-a716-44665544',
            UuidViolationCode::INVALID_FORMAT,
        ];

        yield 'wrong type' => [
            42,
            CommonViolationCode::WRONG_TYPE,
        ];
    }

    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        ?ViolationCodeInterface $expected,
    ): void {
        $result = $this->runRule(
            rule: new UuidRule(),
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
