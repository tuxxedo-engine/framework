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

namespace Unit\Validator\Rule\DateTime;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\DateTime\DateTimeRule;
use Tuxxedo\Validator\Rule\DateTime\DateTimeViolationCode;
use Tuxxedo\Validator\Rule\DateTime\DateTimeViolationContext;
use Tuxxedo\Validator\ViolationCodeInterface;

class DateTimeRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: ?string, 2: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'iso date without format' => [
            '2026-08-16',
            null,
            null,
        ];

        yield 'iso datetime without format' => [
            '2026-08-16T10:30:00',
            null,
            null,
        ];

        yield 'garbage without format' => [
            'nope',
            null,
            DateTimeViolationCode::INVALID_FORMAT,
        ];

        yield 'matches specific format' => [
            '16/08/2026',
            'd/m/Y',
            null,
        ];

        yield 'mismatches specific format' => [
            '2026-08-16',
            'd/m/Y',
            DateTimeViolationCode::INVALID_FORMAT,
        ];

        yield 'wrong type' => [
            42,
            null,
            CommonViolationCode::WRONG_TYPE,
        ];
    }

    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        ?string $format,
        ?ViolationCodeInterface $expected,
    ): void {
        $result = $this->runRule(
            rule: new DateTimeRule(
                format: $format,
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

    public function testInvalidFormatCarriesFormatInContext(): void
    {
        $result = $this->runRule(
            rule: new DateTimeRule(
                format: 'd/m/Y',
            ),
            value: 'garbage',
        );

        self::assertNotNull($result);
        self::assertInstanceOf(DateTimeViolationContext::class, $result->context);
        self::assertSame('d/m/Y', $result->context->format);
    }
}
