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

namespace Unit\Validator\Rule\Regex;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\Regex\RegexRule;
use Tuxxedo\Validator\Rule\Regex\RegexViolationCode;
use Tuxxedo\Validator\ViolationCodeInterface;

class RegexRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: string, 2: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'match' => [
            'abc123',
            '/^[a-z0-9]+$/',
            null,
        ];

        yield 'no match' => [
            'ABC',
            '/^[a-z]+$/',
            RegexViolationCode::NO_MATCH,
        ];

        yield 'wrong type' => [
            42,
            '/./',
            CommonViolationCode::WRONG_TYPE,
        ];
    }

    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        string $pattern,
        ViolationCodeInterface|null $expected,
    ): void {
        $result = $this->runRule(
            rule: new RegexRule(
                pattern: $pattern,
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
}
