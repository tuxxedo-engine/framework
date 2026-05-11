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
use Tuxxedo\View\Lumi\Library\Function\PhpFunction;

class PhpFunctionTest extends TestCase
{
    public function testCallDelegatesToPhpFunction(): void
    {
        $function = new PhpFunction(name: 'strtolower');

        self::assertSame(
            'hello',
            $function->call(
                [
                    'HELLO',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallUsesMappedNameWhenProvided(): void
    {
        $function = new PhpFunction(
            name: 'my_upper',
            mappedName: 'strtoupper',
        );

        self::assertSame(
            'HELLO',
            $function->call(
                [
                    'hello',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallPassesAllArgumentsToPhpFunction(): void
    {
        $function = new PhpFunction(name: 'str_pad');

        self::assertSame(
            'hello     ',
            $function->call(
                [
                    'hello',
                    10,
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
