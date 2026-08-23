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

namespace Unit\Mail;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Mail\Header;
use Tuxxedo\Mail\MailException;

class HeaderTest extends TestCase
{
    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function providesInvalidNames(): \Generator
    {
        yield 'empty' => [
            '',
        ];

        yield 'contains space' => [
            'X Custom',
        ];

        yield 'contains colon' => [
            'X:Custom',
        ];

        yield 'contains newline' => [
            "X-Custom\r\n",
        ];

        yield 'contains null byte' => [
            "X-Custom\x00",
        ];
    }

    #[DataProvider('providesInvalidNames')]
    public function testConstructorRejectsInvalidName(
        string $name,
    ): void {
        try {
            new Header(
                name: $name,
                value: 'ok',
            );

            self::fail('Expected MailException was not thrown');
        } catch (MailException $exception) {
            self::assertStringContainsString('header name', \strtolower($exception->getMessage()));
        }
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function providesInvalidValues(): \Generator
    {
        yield 'carriage return' => [
            "line one\rline two",
        ];

        yield 'line feed' => [
            "line one\nline two",
        ];

        yield 'crlf' => [
            "line one\r\nInjected: yes",
        ];

        yield 'null byte' => [
            "safe\x00still",
        ];
    }

    #[DataProvider('providesInvalidValues')]
    public function testConstructorRejectsInvalidValue(
        string $value,
    ): void {
        try {
            new Header(
                name: 'X-Custom',
                value: $value,
            );

            self::fail('Expected MailException was not thrown');
        } catch (MailException $exception) {
            self::assertStringContainsString('invalid value', \strtolower($exception->getMessage()));
        }
    }

    public function testIsIsCaseInsensitive(): void
    {
        $header = new Header(
            name: 'List-Unsubscribe',
            value: '<https://example.com/u>',
        );

        self::assertTrue($header->is('list-unsubscribe'));
        self::assertTrue($header->is('LIST-UNSUBSCRIBE'));
        self::assertTrue($header->is('List-Unsubscribe'));
        self::assertFalse($header->is('X-Custom'));
    }

    public function testWithValueReturnsNewInstanceAndPreservesName(): void
    {
        $original = new Header(
            name: 'X-Campaign',
            value: 'first',
        );

        $updated = $original->withValue(
            value: 'second',
        );

        self::assertNotSame($original, $updated);
        self::assertSame('first', $original->value);
        self::assertSame('second', $updated->value);
        self::assertSame('X-Campaign', $updated->name);
    }

    public function testWithValueRevalidatesAndThrowsOnInvalidValue(): void
    {
        $original = new Header(
            name: 'X-Campaign',
            value: 'first',
        );

        try {
            (void) $original->withValue(
                value: "corrupt\r\ninjection",
            );

            self::fail('Expected MailException was not thrown');
        } catch (MailException $exception) {
            self::assertStringContainsString('invalid value', \strtolower($exception->getMessage()));
            self::assertSame('first', $original->value);
        }
    }
}
