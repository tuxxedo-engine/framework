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

namespace Unit\Validator\Rule\SuffixedWith;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\SuffixedWith\SuffixedWithRule;
use Tuxxedo\Validator\Rule\SuffixedWith\SuffixedWithViolationCode;
use Tuxxedo\Validator\Rule\SuffixedWith\SuffixedWithViolationContext;
use Tuxxedo\Validator\ViolationCodeInterface;

class SuffixedWithRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: string, 2: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'matching suffix' => [
            'name.txt',
            '.txt',
            null,
        ];

        yield 'missing suffix' => [
            'name.pdf',
            '.txt',
            SuffixedWithViolationCode::MISSING_SUFFIX,
        ];

        yield 'wrong type' => [
            42,
            '.txt',
            CommonViolationCode::WRONG_TYPE,
        ];

        yield 'null skipped' => [
            null,
            '.txt',
            null,
        ];
    }

    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        string $suffix,
        ?ViolationCodeInterface $expected,
    ): void {
        $result = $this->runRule(
            rule: new SuffixedWithRule(
                suffix: $suffix,
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

    public function testViolationCarriesSuffixInContext(): void
    {
        $result = $this->runRule(
            rule: new SuffixedWithRule(
                suffix: '.txt',
            ),
            value: 'name.pdf',
        );

        self::assertNotNull($result);
        self::assertInstanceOf(SuffixedWithViolationContext::class, $result->context);
        self::assertSame('.txt', $result->context->suffix);
    }
}
