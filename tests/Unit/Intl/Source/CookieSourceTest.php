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

namespace Unit\Intl\Source;

use PHPUnit\Framework\TestCase;
use Support\Http\Request\Context\StubHeaderContext;
use Support\Http\Request\Context\StubInputContext;
use Support\Http\Request\Context\StubUploadedFilesContext;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Intl\Source\CookieSource;

class CookieSourceTest extends TestCase
{
    /**
     * @param array<string, string> $cookies
     */
    private function requestWithCookies(
        array $cookies,
    ): Request {
        return new Request(
            headers: new StubHeaderContext(),
            cookies: new StubInputContext(
                values: $cookies,
            ),
            get: new StubInputContext(),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
        );
    }

    public function testReturnsNullWhenCookieMissing(): void
    {
        $source = new CookieSource(
            request: $this->requestWithCookies(
                cookies: [],
            ),
            cookieName: 'lang',
        );

        self::assertNull($source->detect());
    }

    public function testReturnsParsedLocaleWhenCookiePresent(): void
    {
        $source = new CookieSource(
            request: $this->requestWithCookies(
                cookies: [
                    'lang' => 'da-DK',
                ],
            ),
            cookieName: 'lang',
        );

        $detected = $source->detect();

        self::assertNotNull($detected);
        self::assertSame('da-DK', $detected->tag);
    }

    public function testReturnsNullWhenCookieValueUnparseable(): void
    {
        $source = new CookieSource(
            request: $this->requestWithCookies(
                cookies: [
                    'lang' => '!!garbage!!',
                ],
            ),
            cookieName: 'lang',
        );

        self::assertNull($source->detect());
    }

    public function testHonoursCustomCookieName(): void
    {
        $source = new CookieSource(
            request: $this->requestWithCookies(
                cookies: [
                    'user_locale' => 'en-US',
                ],
            ),
            cookieName: 'user_locale',
        );

        $detected = $source->detect();

        self::assertNotNull($detected);
        self::assertSame('en-US', $detected->tag);
    }

    public function testDetectsOncePerSourceInstance(): void
    {
        $source = new CookieSource(
            request: $this->requestWithCookies(
                cookies: [
                    'lang' => 'da-DK',
                ],
            ),
            cookieName: 'lang',
        );

        $first = $source->detect();

        self::assertNotNull($first);
        self::assertSame($first, $source->detect());
    }
}
