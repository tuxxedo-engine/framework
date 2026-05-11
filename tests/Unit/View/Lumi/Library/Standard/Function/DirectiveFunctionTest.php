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

namespace Unit\View\Lumi\Library\Standard\Function;

use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Runtime\StubRuntimeContext;
use Tuxxedo\View\Lumi\Library\Standard\Function\DirectiveFunction;
use Tuxxedo\View\Lumi\Runtime\RuntimeException;

class DirectiveFunctionTest extends TestCase
{
    public function testCallReturnsDirectiveValue(): void
    {
        self::assertSame(
            'bar',
            (new DirectiveFunction())->call(
                [
                    'foo',
                ],
                fn () => new StubRuntimeContext(
                    directives: [
                        'foo' => 'bar',
                    ],
                ),
            ),
        );
    }

    public function testCallThrowsForMissingDirective(): void
    {
        $this->expectException(RuntimeException::class);

        (new DirectiveFunction())->call(
            [
                'missing',
            ],
            fn () => new StubRuntimeContext(),
        );
    }
}
