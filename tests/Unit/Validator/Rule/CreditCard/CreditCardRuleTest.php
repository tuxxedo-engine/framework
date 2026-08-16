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

namespace Unit\Validator\Rule\CreditCard;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\CreditCard\CreditCardRule;
use Tuxxedo\Validator\Rule\CreditCard\CreditCardViolationCode;
use Tuxxedo\Validator\ViolationCodeInterface;

class CreditCardRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'valid visa' => [
            '4111111111111111',
            null,
        ];

        yield 'valid visa with doubled digits over 9' => [
            '4532015112830366',
            null,
        ];

        yield 'valid with spaces' => [
            '4111 1111 1111 1111',
            null,
        ];

        yield 'valid with hyphens' => [
            '4111-1111-1111-1111',
            null,
        ];

        yield 'wrong length' => [
            '12345',
            CreditCardViolationCode::INVALID_FORMAT,
        ];

        yield 'non digit' => [
            '4111abcd11111111',
            CreditCardViolationCode::INVALID_FORMAT,
        ];

        yield 'invalid checksum' => [
            '4111111111111112',
            CreditCardViolationCode::INVALID_CHECKSUM,
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
            rule: new CreditCardRule(),
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
