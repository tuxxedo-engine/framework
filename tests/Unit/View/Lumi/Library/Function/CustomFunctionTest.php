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

namespace Unit\View\Lumi\Library\Function;

use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Runtime\StubRuntimeContext;
use Tuxxedo\View\Lumi\Library\Function\CustomFunction;

class CustomFunctionTest extends TestCase
{
    public function testCallInvokesImplementationWithArguments(): void
    {
        $function = new CustomFunction(
            name: 'join',
            implementation: static fn (array $args, \Closure $context): string => \join('', \array_filter($args, \is_string(...))),
        );

        self::assertSame(
            'hello world',
            $function->call(
                [
                    'hello ',
                    'world',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallPassesContextClosureToImplementation(): void
    {
        $context = static fn (): StubRuntimeContext => new StubRuntimeContext();
        $capturedContext = null;

        $function = new CustomFunction(
            name: 'test',
            implementation: static function (array $args, \Closure $ctx) use (&$capturedContext): mixed {
                $capturedContext = $ctx;

                return null;
            },
        );

        $function->call(
            [],
            $context,
        );

        self::assertSame($context, $capturedContext);
    }

    public function testCallReturnsImplementationReturnValue(): void
    {
        $function = new CustomFunction(
            name: 'sum',
            implementation: static fn (array $args, \Closure $context): int => \array_sum(\array_filter($args, \is_int(...))),
        );

        self::assertSame(
            6,
            $function->call(
                [
                    1,
                    2,
                    3,
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
