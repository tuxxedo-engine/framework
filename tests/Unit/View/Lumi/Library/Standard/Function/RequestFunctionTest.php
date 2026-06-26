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
use Support\Http\Request\Context\StubBodyContext;
use Support\Http\Request\Context\StubHeaderContext;
use Support\Http\Request\Context\StubInputContext;
use Support\Http\Request\Context\StubUploadedFilesContext;
use Support\View\Lumi\Runtime\StubRuntimeContext;
use Tuxxedo\Container\Container;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\View\Lumi\Library\Standard\Function\RequestFunction;

class RequestFunctionTest extends TestCase
{
    public function testCallReturnsServerContext(): void
    {
        $request = new Request(
            headers: new StubHeaderContext(),
            cookies: new StubInputContext(),
            get: new StubInputContext(),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
            body: new StubBodyContext(),
        );

        $function = new RequestFunction(
            container: (new Container())->singleton(
                class: $request,
            ),
        );

        self::assertSame(
            $request,
            $function->call(
                [],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
