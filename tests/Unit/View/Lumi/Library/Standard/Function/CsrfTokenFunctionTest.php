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
use Support\Security\Csrf\Storage\StubCsrfStorageHandler;
use Support\View\Lumi\Runtime\StubRuntimeContext;
use Tuxxedo\Container\Container;
use Tuxxedo\Security\Csrf\CsrfManager;
use Tuxxedo\View\Lumi\Library\Standard\Function\CsrfTokenFunction;

class CsrfTokenFunctionTest extends TestCase
{
    private function makeContainerWithCsrfManager(
        ?string $storedToken = null,
    ): Container {
        return (new Container())->singleton(
            class: new CsrfManager(
                storage: new StubCsrfStorageHandler(
                    token: $storedToken,
                ),
            ),
        );
    }

    public function testCallReturnsCurrentToken(): void
    {
        $function = new CsrfTokenFunction(
            container: $this->makeContainerWithCsrfManager(
                storedToken: 'token-abc',
            ),
        );

        self::assertSame(
            'token-abc',
            $function->call(
                [],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallReturnsRegeneratedTokenWhenNoneStored(): void
    {
        $function = new CsrfTokenFunction(
            container: $this->makeContainerWithCsrfManager(),
        );

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{64}$/',
            $function->call(
                [],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallReturnsConsistentTokenAcrossInvocations(): void
    {
        $function = new CsrfTokenFunction(
            container: $this->makeContainerWithCsrfManager(),
        );

        self::assertSame(
            $function->call(
                [],
                static fn () => new StubRuntimeContext(),
            ),
            $function->call(
                [],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
