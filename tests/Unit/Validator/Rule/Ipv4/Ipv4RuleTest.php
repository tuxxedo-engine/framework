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

namespace Unit\Validator\Rule\Ipv4;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\Ipv4\Ipv4Rule;
use Tuxxedo\Validator\Rule\Ipv4\Ipv4ViolationCode;
use Tuxxedo\Validator\ViolationCodeInterface;

class Ipv4RuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'valid' => [
            '10.0.0.1',
            null,
        ];

        yield 'ipv6 fails' => [
            '2001:db8::1',
            Ipv4ViolationCode::INVALID_FORMAT,
        ];

        yield 'invalid' => [
            '999.0.0.1',
            Ipv4ViolationCode::INVALID_FORMAT,
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
            rule: new Ipv4Rule(),
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
