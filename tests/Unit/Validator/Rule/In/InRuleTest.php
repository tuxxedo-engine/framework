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

namespace Unit\Validator\Rule\In;

use Fixture\Validator\FixtureStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\Rule\In\InRule;
use Tuxxedo\Validator\Rule\In\InViolationCode;
use Tuxxedo\Validator\Rule\In\InViolationContext;
use Tuxxedo\Validator\ViolationCodeInterface;

class InRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: list<string|int|\BackedEnum>, 2: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'string in list' => [
            'apple',
            [
                'apple',
                'banana',
            ],
            null,
        ];

        yield 'int in list' => [
            3,
            [
                1,
                2,
                3,
            ],
            null,
        ];

        yield 'not in list' => [
            'cherry',
            [
                'apple',
                'banana',
            ],
            InViolationCode::NOT_IN_LIST,
        ];

        yield 'enum value matches string' => [
            'active',
            [
                FixtureStatus::ACTIVE,
                FixtureStatus::INACTIVE,
            ],
            null,
        ];

        yield 'enum instance matches enum entry' => [
            FixtureStatus::ACTIVE,
            [
                FixtureStatus::ACTIVE,
            ],
            null,
        ];

        yield 'string case mismatch' => [
            'Apple',
            [
                'apple',
            ],
            InViolationCode::NOT_IN_LIST,
        ];

        yield 'type mismatch strict' => [
            '1',
            [
                1,
                2,
            ],
            InViolationCode::NOT_IN_LIST,
        ];
    }

    /**
     * @param list<string|int|\BackedEnum> $values
     */
    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        array $values,
        ViolationCodeInterface|null $expected,
    ): void {
        $result = $this->runRule(
            rule: new InRule(
                values: $values,
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

    public function testViolationCarriesAllowedListInContext(): void
    {
        $result = $this->runRule(
            rule: new InRule(
                values: [
                    'red',
                    'green',
                    'blue',
                ],
            ),
            value: 'purple',
        );

        self::assertNotNull($result);
        self::assertInstanceOf(InViolationContext::class, $result->context);
        self::assertSame(
            [
                'red',
                'green',
                'blue',
            ],
            $result->context->allowed,
        );
    }
}
