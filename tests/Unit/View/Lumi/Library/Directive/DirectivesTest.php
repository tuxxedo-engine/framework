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

namespace Unit\View\Lumi\Library\Directive;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Library\Directive\Directives;
use Tuxxedo\View\Lumi\Runtime\RuntimeException;

class DirectivesTest extends TestCase
{
    public function testHasReturnsTrueForExistingDirective(): void
    {
        $directives = new Directives(
            directives: [
                'foo' => 'bar',
            ],
        );

        self::assertTrue($directives->has('foo'));
    }

    public function testHasReturnsFalseForMissingDirective(): void
    {
        self::assertFalse((new Directives([]))->has('foo'));
    }

    /**
     * @return \Generator<array{0: \Closure(Directives): mixed}>
     */
    public static function missingDirectiveDataProvider(): \Generator
    {
        yield [
            static fn (Directives $d): mixed => $d->asString('missing'),
        ];

        yield [
            static fn (Directives $d): mixed => $d->asInt('missing'),
        ];

        yield [
            static fn (Directives $d): mixed => $d->asFloat('missing'),
        ];

        yield [
            static fn (Directives $d): mixed => $d->asBool('missing'),
        ];

        yield [
            static fn (Directives $d): mixed => $d->isNull('missing'),
        ];
    }

    /**
     * @param \Closure(Directives): mixed $call
     */
    #[DataProvider('missingDirectiveDataProvider')]
    public function testThrowsForMissingDirective(
        \Closure $call,
    ): void {
        $this->expectException(RuntimeException::class);

        $call(new Directives([]));
    }

    /**
     * @return \Generator<array{0: \Closure(Directives): mixed}>
     */
    public static function wrongTypeDirectiveDataProvider(): \Generator
    {
        yield [
            static fn (Directives $d): mixed => $d->asString('val'),
        ];

        yield [
            static fn (Directives $d): mixed => $d->asInt('val'),
        ];

        yield [
            static fn (Directives $d): mixed => $d->asFloat('val'),
        ];

        yield [
            static fn (Directives $d): mixed => $d->asBool('val'),
        ];
    }

    /**
     * @param \Closure(Directives): mixed $call
     */
    #[DataProvider('wrongTypeDirectiveDataProvider')]
    public function testThrowsForWrongType(
        \Closure $call,
    ): void {
        $this->expectException(RuntimeException::class);

        $call(
            new Directives(
                directives: [
                    'val' => null,
                ],
            ),
        );
    }

    public function testAsStringReturnsValue(): void
    {
        $directives = new Directives(
            directives: [
                'greeting' => 'hello',
            ],
        );

        self::assertSame('hello', $directives->asString('greeting'));
    }

    public function testAsIntReturnsValue(): void
    {
        $directives = new Directives(
            directives: [
                'count' => 42,
            ],
        );

        self::assertSame(42, $directives->asInt('count'));
    }

    public function testAsFloatReturnsValue(): void
    {
        $directives = new Directives(
            directives: [
                'ratio' => 3.14,
            ],
        );

        self::assertSame(3.14, $directives->asFloat('ratio'));
    }

    public function testAsBoolReturnsValue(): void
    {
        $directives = new Directives(
            directives: [
                'enabled' => true,
            ],
        );

        self::assertTrue($directives->asBool('enabled'));
    }

    public function testIsNullReturnsTrueForNullValue(): void
    {
        $directives = new Directives(
            directives: [
                'optional' => null,
            ],
        );

        self::assertTrue($directives->isNull('optional'));
    }

    public function testIsNullReturnsFalseForNonNullValue(): void
    {
        $directives = new Directives(
            directives: [
                'optional' => 'set',
            ],
        );

        self::assertFalse($directives->isNull('optional'));
    }
}
