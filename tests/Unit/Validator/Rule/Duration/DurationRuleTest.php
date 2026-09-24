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

namespace Unit\Validator\Rule\Duration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\Duration\DurationRule;
use Tuxxedo\Validator\Rule\Duration\DurationViolationCode;
use Tuxxedo\Validator\Rule\Duration\DurationViolationContext;
use Tuxxedo\Validator\ViolationCodeInterface;

class DurationRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'null skipped' => [
            null,
            null,
        ];

        yield 'valid iso duration' => [
            'PT1H30M',
            null,
        ];

        yield 'valid negative duration' => [
            '-PT1H',
            null,
        ];

        yield 'garbage string' => [
            'nope',
            DurationViolationCode::INVALID_FORMAT,
        ];

        yield 'wrong type int' => [
            42,
            CommonViolationCode::WRONG_TYPE,
        ];

        yield 'wrong type array' => [
            [
                'PT1H',
            ],
            CommonViolationCode::WRONG_TYPE,
        ];

        yield 'duration instance accepted' => [
            Duration::fromMinutes(minutes: 30),
            null,
        ];
    }

    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        ?ViolationCodeInterface $expected,
    ): void {
        $result = $this->runRule(
            rule: new DurationRule(),
            value: $value,
        );

        if ($expected === null) {
            self::assertNull($result);

            return;
        }

        self::assertNotNull($result);
        self::assertSame($expected, $result->code);
    }

    public function testInvalidFormatCarriesReasonInContext(): void
    {
        $result = $this->runRule(
            rule: new DurationRule(),
            value: 'nope',
        );

        self::assertNotNull($result);
        self::assertInstanceOf(DurationViolationContext::class, $result->context);
        self::assertStringContainsString('Malformed ISO 8601', $result->context->reason);
    }
}
