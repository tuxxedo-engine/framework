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

namespace Unit\View\Lumi\Library\Filter;

use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Runtime\StubRuntimeContext;
use Tuxxedo\View\Lumi\Library\Filter\CustomFilter;

class CustomFilterTest extends TestCase
{
    public function testCallInvokesImplementationWithValue(): void
    {
        $filter = new CustomFilter(
            name: 'upper',
            implementation: static function (mixed $value, \Closure $context): string {
                /** @var string $value */

                return \strtoupper($value);
            },
        );

        self::assertSame(
            'HELLO',
            $filter->call(
                'hello',
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallPassesContextClosureToImplementation(): void
    {
        $context = static fn (): StubRuntimeContext => new StubRuntimeContext();
        $capturedContext = null;

        $filter = new CustomFilter(
            name: 'test',
            implementation: static function (mixed $value, \Closure $ctx) use (&$capturedContext): mixed {
                $capturedContext = $ctx;

                return $value;
            },
        );

        $filter->call('foo', $context);

        self::assertSame($context, $capturedContext);
    }

    public function testCallReturnsImplementationReturnValue(): void
    {
        $filter = new CustomFilter(
            name: 'double',
            implementation: static function (mixed $value, \Closure $context): int {
                /** @var int $value */

                return $value * 2;
            },
        );

        self::assertSame(
            84,
            $filter->call(
                42,
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
