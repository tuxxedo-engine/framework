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

namespace Unit\Validator\Rule\Boolean;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\Boolean\BooleanRule;
use Tuxxedo\Validator\ViolationCodeInterface;

class BooleanRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: bool, 2: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'true is bool' => [
            true,
            false,
            null,
        ];

        yield 'false is bool' => [
            false,
            false,
            null,
        ];

        yield 'string true lenient' => [
            'true',
            false,
            null,
        ];

        yield 'string false lenient' => [
            'false',
            false,
            null,
        ];

        yield 'string yes lenient' => [
            'yes',
            false,
            null,
        ];

        yield 'string no lenient' => [
            'no',
            false,
            null,
        ];

        yield 'string one lenient' => [
            '1',
            false,
            null,
        ];

        yield 'string zero lenient' => [
            '0',
            false,
            null,
        ];

        yield 'int one lenient' => [
            1,
            false,
            null,
        ];

        yield 'string true fails strict' => [
            'true',
            true,
            CommonViolationCode::WRONG_TYPE,
        ];

        yield 'int one fails strict' => [
            1,
            true,
            CommonViolationCode::WRONG_TYPE,
        ];

        yield 'garbage string fails lenient' => [
            'nope',
            false,
            CommonViolationCode::WRONG_TYPE,
        ];

        yield 'null fails' => [
            null,
            false,
            CommonViolationCode::WRONG_TYPE,
        ];
    }

    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        bool $strict,
        ViolationCodeInterface|null $expected,
    ): void {
        $result = $this->runRule(
            rule: new BooleanRule(
                strict: $strict,
            ),
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
