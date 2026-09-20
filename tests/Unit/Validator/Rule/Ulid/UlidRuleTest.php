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

namespace Unit\Validator\Rule\Ulid;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\Ulid\UlidRule;
use Tuxxedo\Validator\Rule\Ulid\UlidViolationCode;
use Tuxxedo\Validator\ViolationCodeInterface;

class UlidRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'valid ulid uppercase' => [
            '01ARZ3NDEKTSV4RRFFQ69G5FAV',
            null,
        ];

        yield 'valid ulid lowercase' => [
            '01arz3ndektsv4rrffq69g5fav',
            null,
        ];

        yield 'too short' => [
            '01ARZ3NDEKTSV4RRFFQ69G5FA',
            UlidViolationCode::INVALID_FORMAT,
        ];

        yield 'contains banned letter I' => [
            '01ARZ3NDEKTSV4RRFFQ69G5FAI',
            UlidViolationCode::INVALID_FORMAT,
        ];

        yield 'wrong type' => [
            42,
            CommonViolationCode::WRONG_TYPE,
        ];

        yield 'null skipped' => [
            null,
            null,
        ];
    }

    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        ?ViolationCodeInterface $expected,
    ): void {
        $result = $this->runRule(
            rule: new UlidRule(),
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
