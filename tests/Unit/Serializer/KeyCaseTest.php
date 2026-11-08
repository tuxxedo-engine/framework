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

namespace Unit\Serializer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Serializer\KeyCase;

class KeyCaseTest extends TestCase
{
    #[DataProvider('caseProvider')]
    public function testApply(
        KeyCase $case,
        string $name,
        string $expected,
    ): void {
        self::assertSame($expected, $case->apply($name));
    }

    /**
     * @return \Generator<string, array{KeyCase, string, string}>
     */
    public static function caseProvider(): \Generator
    {
        yield 'camel' => [
            KeyCase::CAMEL,
            'FirstName',
            'firstName',
        ];

        yield 'pascal' => [
            KeyCase::PASCAL,
            'firstName',
            'FirstName',
        ];

        yield 'snake' => [
            KeyCase::SNAKE,
            'billingAddress2Line',
            'billing_address2_line',
        ];

        yield 'kebab' => [
            KeyCase::KEBAB,
            'firstName',
            'first-name',
        ];

        yield 'snake single word' => [
            KeyCase::SNAKE,
            'id',
            'id',
        ];

        yield 'snake acronym' => [
            KeyCase::SNAKE,
            'userID',
            'user_id',
        ];
    }
}
