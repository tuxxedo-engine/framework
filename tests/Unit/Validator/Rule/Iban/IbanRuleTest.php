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

namespace Unit\Validator\Rule\Iban;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\Iban\IbanRule;
use Tuxxedo\Validator\Rule\Iban\IbanViolationCode;
use Tuxxedo\Validator\ViolationCodeInterface;

class IbanRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'valid german iban' => [
            'DE89370400440532013000',
            null,
        ];

        yield 'valid british iban with spaces' => [
            'GB82 WEST 1234 5698 7654 32',
            null,
        ];

        yield 'invalid format' => [
            'INVALID',
            IbanViolationCode::INVALID_FORMAT,
        ];

        yield 'invalid checksum' => [
            'DE89370400440532013001',
            IbanViolationCode::INVALID_CHECKSUM,
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
            rule: new IbanRule(),
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
