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

namespace Unit\Validator\Rule\CharacterLength;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\CharacterLength\CharacterLengthRule;
use Tuxxedo\Validator\Rule\CharacterLength\CharacterLengthViolationCode;
use Tuxxedo\Validator\Rule\CharacterLength\CharacterLengthViolationContext;
use Tuxxedo\Validator\ViolationCodeInterface;

class CharacterLengthRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: ?int, 2: ?int, 3: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'ascii within bounds' => [
            'hello',
            3,
            10,
            null,
        ];

        yield 'multibyte within bounds' => [
            'héllo',
            3,
            10,
            null,
        ];

        yield 'multibyte counts as characters not bytes' => [
            'åäö',
            3,
            3,
            null,
        ];

        yield 'too short' => [
            'hi',
            3,
            null,
            CharacterLengthViolationCode::TOO_SHORT,
        ];

        yield 'too long' => [
            'this-is-much-longer-than-allowed',
            null,
            10,
            CharacterLengthViolationCode::TOO_LONG,
        ];

        yield 'wrong type' => [
            42,
            null,
            null,
            CommonViolationCode::WRONG_TYPE,
        ];

        yield 'null skipped' => [
            null,
            null,
            null,
            null,
        ];
    }

    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        ?int $min,
        ?int $max,
        ?ViolationCodeInterface $expected,
    ): void {
        $result = $this->runRule(
            rule: new CharacterLengthRule(
                min: $min,
                max: $max,
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

    public function testTooShortCarriesMinAndActualInContext(): void
    {
        $result = $this->runRule(
            rule: new CharacterLengthRule(
                min: 4,
            ),
            value: 'ab',
        );

        self::assertNotNull($result);
        self::assertInstanceOf(CharacterLengthViolationContext::class, $result->context);
        self::assertSame(4, $result->context->min);
        self::assertSame(2, $result->context->actual);
    }

    public function testTooLongCarriesMaxAndActualInContext(): void
    {
        $result = $this->runRule(
            rule: new CharacterLengthRule(
                max: 3,
            ),
            value: 'abcdef',
        );

        self::assertNotNull($result);
        self::assertInstanceOf(CharacterLengthViolationContext::class, $result->context);
        self::assertSame(3, $result->context->max);
        self::assertSame(6, $result->context->actual);
    }
}
