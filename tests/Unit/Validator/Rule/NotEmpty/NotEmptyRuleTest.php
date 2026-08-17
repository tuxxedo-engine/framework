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

namespace Unit\Validator\Rule\NotEmpty;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\Rule\NotEmpty\NotEmptyRule;
use Tuxxedo\Validator\Rule\NotEmpty\NotEmptyViolationCode;
use Tuxxedo\Validator\ViolationCodeInterface;

class NotEmptyRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'non-empty string' => [
            'hello',
            null,
        ];

        yield 'non-empty array' => [
            [
                1,
                2,
            ],
            null,
        ];

        yield 'string zero is non-empty' => [
            '0',
            null,
        ];

        yield 'int zero is non-empty' => [
            0,
            null,
        ];

        yield 'false is non-empty' => [
            false,
            null,
        ];

        yield 'empty string fails' => [
            '',
            NotEmptyViolationCode::EMPTY_VALUE,
        ];

        yield 'empty array fails' => [
            [],
            NotEmptyViolationCode::EMPTY_VALUE,
        ];

        yield 'null fails' => [
            null,
            NotEmptyViolationCode::EMPTY_VALUE,
        ];
    }

    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        ?ViolationCodeInterface $expected,
    ): void {
        $result = $this->runRule(
            rule: new NotEmptyRule(),
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
