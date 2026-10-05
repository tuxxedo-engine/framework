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
use Tuxxedo\Security\Csrf\CsrfManager;
use Tuxxedo\View\Lumi\Library\Standard\Function\CsrfFunctions;

class CsrfFunctionsTest extends TestCase
{
    private function makeFunctions(
        ?string $storedToken = null,
        string $fieldName = '__csrf_token',
    ): CsrfFunctions {
        return new CsrfFunctions(
            manager: new CsrfManager(
                storage: new StubCsrfStorageHandler(
                    token: $storedToken,
                ),
                fieldName: $fieldName,
            ),
        );
    }

    public function testCsrfFieldReturnsHiddenInputWithDefaultsAndStoredToken(): void
    {
        self::assertSame(
            '<input type="hidden" name="__csrf_token" value="token-abc">',
            $this->makeFunctions(
                storedToken: 'token-abc',
            )->csrfField(),
        );
    }

    public function testCsrfFieldEscapesTokenForHtmlAttributeContext(): void
    {
        self::assertSame(
            '<input type="hidden" name="__csrf_token" value="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;&amp;">',
            $this->makeFunctions(
                storedToken: '"><script>alert(1)</script>&',
            )->csrfField(),
        );
    }

    public function testCsrfFieldReflectsCustomFieldName(): void
    {
        self::assertSame(
            '<input type="hidden" name="authenticity_token" value="token-abc">',
            $this->makeFunctions(
                storedToken: 'token-abc',
                fieldName: 'authenticity_token',
            )->csrfField(),
        );
    }

    public function testCsrfFieldRegeneratesTokenWhenNoneStored(): void
    {
        self::assertMatchesRegularExpression(
            '/^<input type="hidden" name="__csrf_token" value="[0-9a-f]{64}">$/',
            $this->makeFunctions()->csrfField(),
        );
    }

    public function testCsrfFieldNameReturnsDefault(): void
    {
        self::assertSame('__csrf_token', $this->makeFunctions()->csrfFieldName());
    }

    public function testCsrfFieldNameReturnsCustom(): void
    {
        self::assertSame(
            'authenticity_token',
            $this->makeFunctions(
                fieldName: 'authenticity_token',
            )->csrfFieldName(),
        );
    }

    public function testCsrfTokenReturnsCurrentToken(): void
    {
        self::assertSame(
            'token-abc',
            $this->makeFunctions(
                storedToken: 'token-abc',
            )->csrfToken(),
        );
    }

    public function testCsrfTokenReturnsRegeneratedTokenWhenNoneStored(): void
    {
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{64}$/',
            $this->makeFunctions()->csrfToken(),
        );
    }

    public function testCsrfTokenReturnsConsistentTokenAcrossInvocations(): void
    {
        $functions = $this->makeFunctions();

        self::assertSame($functions->csrfToken(), $functions->csrfToken());
    }
}
