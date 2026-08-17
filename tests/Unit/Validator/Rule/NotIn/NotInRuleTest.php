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

namespace Unit\Validator\Rule\NotIn;

use Fixture\Validator\FixtureStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\Rule\NotIn\NotInRule;
use Tuxxedo\Validator\Rule\NotIn\NotInViolationCode;
use Tuxxedo\Validator\Rule\NotIn\NotInViolationContext;
use Tuxxedo\Validator\ViolationCodeInterface;

class NotInRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: list<string|int|\BackedEnum>, 2: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'not in disallowed list' => [
            'cherry',
            [
                'apple',
                'banana',
            ],
            null,
        ];

        yield 'in disallowed list' => [
            'apple',
            [
                'apple',
                'banana',
            ],
            NotInViolationCode::IN_LIST,
        ];

        yield 'enum instance in disallowed' => [
            FixtureStatus::PENDING,
            [
                FixtureStatus::PENDING,
            ],
            NotInViolationCode::IN_LIST,
        ];
    }

    /**
     * @param list<string|int|\BackedEnum> $values
     */
    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        array $values,
        ?ViolationCodeInterface $expected,
    ): void {
        $result = $this->runRule(
            rule: new NotInRule(
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

    public function testViolationCarriesDisallowedListInContext(): void
    {
        $result = $this->runRule(
            rule: new NotInRule(
                values: [
                    'reserved',
                    'admin',
                ],
            ),
            value: 'admin',
        );

        self::assertNotNull($result);
        self::assertInstanceOf(NotInViolationContext::class, $result->context);
        self::assertSame(
            [
                'reserved',
                'admin',
            ],
            $result->context->disallowed,
        );
    }
}
