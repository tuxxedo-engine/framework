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
use Tuxxedo\Container\Container;
use Tuxxedo\Http\Url\Url;
use Tuxxedo\View\Lumi\Library\Standard\Function\UrlFunction;

class UrlFunctionTest extends TestCase
{
    private function makeFunction(
        string $base,
    ): UrlFunction {
        return new UrlFunction(
            container: (new Container())->singleton(
                class: new Url($base),
            ),
        );
    }

    public function testCallReturnsFullUrl(): void
    {
        self::assertSame(
            'https://example.com/about',
            $this->makeFunction('https://example.com/')->call(
                [
                    'about',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallStripsLeadingSlashFromPath(): void
    {
        self::assertSame(
            'https://example.com/about',
            $this->makeFunction('https://example.com/')->call(
                [
                    '/about',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
