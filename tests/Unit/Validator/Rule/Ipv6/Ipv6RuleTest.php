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

namespace Unit\Validator\Rule\Ipv6;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\Ipv6\Ipv6Rule;
use Tuxxedo\Validator\Rule\Ipv6\Ipv6ViolationCode;
use Tuxxedo\Validator\ViolationCodeInterface;

class Ipv6RuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'valid' => [
            '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            null,
        ];

        yield 'shortened' => [
            '::1',
            null,
        ];

        yield 'ipv4 fails' => [
            '192.168.1.1',
            Ipv6ViolationCode::INVALID_FORMAT,
        ];

        yield 'invalid' => [
            'zzz::1',
            Ipv6ViolationCode::INVALID_FORMAT,
        ];

        yield 'wrong type' => [
            42,
            CommonViolationCode::WRONG_TYPE,
        ];

        yield 'null skipped' => [
            null,
            null,
        ];
    }

    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        ?ViolationCodeInterface $expected,
    ): void {
        $result = $this->runRule(
            rule: new Ipv6Rule(),
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
