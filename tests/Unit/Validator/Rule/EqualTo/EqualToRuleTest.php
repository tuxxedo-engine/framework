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

namespace Unit\Validator\Rule\EqualTo;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\Rule\EqualTo\EqualToRule;
use Tuxxedo\Validator\Rule\EqualTo\EqualToViolationCode;
use Tuxxedo\Validator\Rule\EqualTo\EqualToViolationContext;
use Tuxxedo\Validator\ViolationCodeInterface;

class EqualToRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: int|float|string|bool|null, 2: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'exact int match' => [
            42,
            42,
            null,
        ];

        yield 'string match' => [
            'foo',
            'foo',
            null,
        ];

        yield 'int-string mismatch strict' => [
            '42',
            42,
            EqualToViolationCode::NOT_EQUAL,
        ];

        yield 'null match' => [
            null,
            null,
            null,
        ];

        yield 'null vs zero mismatch' => [
            0,
            null,
            EqualToViolationCode::NOT_EQUAL,
        ];

        yield 'bool match' => [
            true,
            true,
            null,
        ];
    }

    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        int|float|string|bool|null $expected,
        ViolationCodeInterface|null $expectedCode,
    ): void {
        $result = $this->runRule(
            rule: new EqualToRule(
                expected: $expected,
            ),
            value: $value,
        );

        if ($expectedCode === null) {
            self::assertNull($result);

            return;
        }

        self::assertNotNull($result);
        self::assertSame($expectedCode, $result->code);
    }

    public function testViolationCarriesExpectedInContext(): void
    {
        $result = $this->runRule(
            rule: new EqualToRule(
                expected: 42,
            ),
            value: 5,
        );

        self::assertNotNull($result);
        self::assertInstanceOf(EqualToViolationContext::class, $result->context);
        self::assertSame(42, $result->context->expected);
    }
}
