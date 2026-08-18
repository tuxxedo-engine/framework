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

namespace Unit\Validator\Rule\Enum;

use Fixture\Validator\FixtureStatus;
use Fixture\Validator\FixtureUnitEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\Rule\Enum\EnumRule;
use Tuxxedo\Validator\Rule\Enum\EnumViolationCode;
use Tuxxedo\Validator\Rule\Enum\EnumViolationContext;
use Tuxxedo\Validator\ViolationCodeInterface;

class EnumRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: class-string<\UnitEnum>, 2: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'matching backed enum instance' => [
            FixtureStatus::ACTIVE,
            FixtureStatus::class,
            null,
        ];

        yield 'matching unit enum instance' => [
            FixtureUnitEnum::ALPHA,
            FixtureUnitEnum::class,
            null,
        ];

        yield 'wrong enum type' => [
            FixtureUnitEnum::ALPHA,
            FixtureStatus::class,
            EnumViolationCode::WRONG_INSTANCE,
        ];

        yield 'non-enum value' => [
            'active',
            FixtureStatus::class,
            EnumViolationCode::WRONG_INSTANCE,
        ];

        yield 'int value' => [
            42,
            FixtureStatus::class,
            EnumViolationCode::WRONG_INSTANCE,
        ];

        yield 'null skipped' => [
            null,
            FixtureStatus::class,
            null,
        ];
    }

    /**
     * @param class-string<\UnitEnum> $enum
     */
    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        string $enum,
        ViolationCodeInterface|null $expected,
    ): void {
        $result = $this->runRule(
            rule: new EnumRule(
                enum: $enum,
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

    public function testViolationCarriesExpectedAndReceivedInContext(): void
    {
        $result = $this->runRule(
            rule: new EnumRule(
                enum: FixtureStatus::class,
            ),
            value: 'not-an-enum',
        );

        self::assertNotNull($result);
        self::assertInstanceOf(EnumViolationContext::class, $result->context);
        self::assertSame(FixtureStatus::class, $result->context->expected);
        self::assertSame('string', $result->context->received);
    }
}
