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

namespace Unit\Validator\Rule\Contains;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\Contains\ContainsRule;
use Tuxxedo\Validator\Rule\Contains\ContainsViolationCode;
use Tuxxedo\Validator\Rule\Contains\ContainsViolationContext;
use Tuxxedo\Validator\ViolationCodeInterface;

class ContainsRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: string, 2: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'contains substring' => [
            'hello world',
            'world',
            null,
        ];

        yield 'missing substring' => [
            'hello',
            'world',
            ContainsViolationCode::MISSING_SUBSTRING,
        ];

        yield 'wrong type' => [
            42,
            'x',
            CommonViolationCode::WRONG_TYPE,
        ];

        yield 'null skipped' => [
            null,
            'x',
            null,
        ];
    }

    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        string $needle,
        ?ViolationCodeInterface $expected,
    ): void {
        $result = $this->runRule(
            rule: new ContainsRule(
                needle: $needle,
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

    public function testViolationCarriesNeedleInContext(): void
    {
        $result = $this->runRule(
            rule: new ContainsRule(
                needle: 'x',
            ),
            value: 'yyy',
        );

        self::assertNotNull($result);
        self::assertInstanceOf(ContainsViolationContext::class, $result->context);
        self::assertSame('x', $result->context->needle);
    }
}
