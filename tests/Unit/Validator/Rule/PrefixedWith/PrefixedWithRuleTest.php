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

namespace Unit\Validator\Rule\PrefixedWith;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\PrefixedWith\PrefixedWithRule;
use Tuxxedo\Validator\Rule\PrefixedWith\PrefixedWithViolationCode;
use Tuxxedo\Validator\Rule\PrefixedWith\PrefixedWithViolationContext;
use Tuxxedo\Validator\ViolationCodeInterface;

class PrefixedWithRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: string, 2: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'matching prefix' => [
            'foo-bar',
            'foo-',
            null,
        ];

        yield 'missing prefix' => [
            'baz',
            'foo-',
            PrefixedWithViolationCode::MISSING_PREFIX,
        ];

        yield 'wrong type' => [
            42,
            'foo-',
            CommonViolationCode::WRONG_TYPE,
        ];
    }

    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        string $prefix,
        ViolationCodeInterface|null $expected,
    ): void {
        $result = $this->runRule(
            rule: new PrefixedWithRule(
                prefix: $prefix,
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

    public function testViolationCarriesPrefixInContext(): void
    {
        $result = $this->runRule(
            rule: new PrefixedWithRule(
                prefix: 'foo-',
            ),
            value: 'baz',
        );

        self::assertNotNull($result);
        self::assertInstanceOf(PrefixedWithViolationContext::class, $result->context);
        self::assertSame('foo-', $result->context->prefix);
    }
}
