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

namespace Unit\Validator\Rule\NotEqualTo;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\Rule\NotEqualTo\NotEqualToRule;
use Tuxxedo\Validator\Rule\NotEqualTo\NotEqualToViolationCode;
use Tuxxedo\Validator\Rule\NotEqualTo\NotEqualToViolationContext;
use Tuxxedo\Validator\ViolationCodeInterface;

class NotEqualToRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: int|float|string|bool|null, 2: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'different int' => [
            5,
            42,
            null,
        ];

        yield 'equal int fails' => [
            42,
            42,
            NotEqualToViolationCode::EQUAL,
        ];

        yield 'int-string strict differs' => [
            '42',
            42,
            null,
        ];

        yield 'null vs zero differs' => [
            0,
            null,
            null,
        ];

        yield 'null equal null fails' => [
            null,
            null,
            NotEqualToViolationCode::EQUAL,
        ];
    }

    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        int|float|string|bool|null $disallowed,
        ?ViolationCodeInterface $expected,
    ): void {
        $result = $this->runRule(
            rule: new NotEqualToRule(
                disallowed: $disallowed,
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

    public function testViolationCarriesDisallowedInContext(): void
    {
        $result = $this->runRule(
            rule: new NotEqualToRule(
                disallowed: 'admin',
            ),
            value: 'admin',
        );

        self::assertNotNull($result);
        self::assertInstanceOf(NotEqualToViolationContext::class, $result->context);
        self::assertSame('admin', $result->context->disallowed);
    }
}
