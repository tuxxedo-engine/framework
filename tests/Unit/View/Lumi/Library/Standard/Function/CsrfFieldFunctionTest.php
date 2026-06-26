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
use Tuxxedo\View\Lumi\Library\Standard\Function\CsrfFieldFunction;

class CsrfFieldFunctionTest extends TestCase
{
    private function makeContainerWithCsrfManager(
        ?string $storedToken = null,
        string $fieldName = '__csrf_token',
    ): Container {
        return (new Container())->singleton(
            class: new CsrfManager(
                storage: new StubCsrfStorageHandler(
                    token: $storedToken,
                ),
                fieldName: $fieldName,
            ),
        );
    }

    public function testCallReturnsHiddenInputWithDefaultFieldNameAndStoredToken(): void
    {
        $function = new CsrfFieldFunction(
            container: $this->makeContainerWithCsrfManager(
                storedToken: 'token-abc',
            ),
        );

        self::assertSame(
            '<input type="hidden" name="__csrf_token" value="token-abc">',
            $function->call(
                [],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallEscapesTokenForHtmlAttributeContext(): void
    {
        $function = new CsrfFieldFunction(
            container: $this->makeContainerWithCsrfManager(
                storedToken: '"><script>alert(1)</script>&',
            ),
        );

        self::assertSame(
            '<input type="hidden" name="__csrf_token" value="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;&amp;">',
            $function->call(
                [],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallReflectsCustomFieldName(): void
    {
        $function = new CsrfFieldFunction(
            container: $this->makeContainerWithCsrfManager(
                storedToken: 'token-abc',
                fieldName: 'authenticity_token',
            ),
        );

        self::assertSame(
            '<input type="hidden" name="authenticity_token" value="token-abc">',
            $function->call(
                [],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallRegeneratesTokenWhenNoneStored(): void
    {
        $function = new CsrfFieldFunction(
            container: $this->makeContainerWithCsrfManager(),
        );

        self::assertMatchesRegularExpression(
            '/^<input type="hidden" name="__csrf_token" value="[0-9a-f]{64}">$/',
            $function->call(
                [],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
