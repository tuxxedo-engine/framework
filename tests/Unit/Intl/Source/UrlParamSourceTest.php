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
use Tuxxedo\Intl\Source\UrlParamSource;

class UrlParamSourceTest extends TestCase
{
    /**
     * @param array<string, string> $query
     */
    private function requestWithQuery(
        array $query,
    ): Request {
        return new Request(
            headers: new StubHeaderContext(),
            cookies: new StubInputContext(),
            get: new StubInputContext(
                values: $query,
            ),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
        );
    }

    public function testReturnsNullWhenParamMissing(): void
    {
        $source = new UrlParamSource(
            request: $this->requestWithQuery(
                query: [],
            ),
            parameterName: 'lang',
        );

        self::assertNull($source->detect());
    }

    public function testReturnsParsedLocaleWhenParamPresent(): void
    {
        $source = new UrlParamSource(
            request: $this->requestWithQuery(
                query: [
                    'lang' => 'da-DK',
                ],
            ),
            parameterName: 'lang',
        );

        $detected = $source->detect();

        self::assertNotNull($detected);
        self::assertSame('da-DK', $detected->tag);
    }

    public function testReturnsNullWhenParamValueUnparseable(): void
    {
        $source = new UrlParamSource(
            request: $this->requestWithQuery(
                query: [
                    'lang' => '!!garbage!!',
                ],
            ),
            parameterName: 'lang',
        );

        self::assertNull($source->detect());
    }

    public function testHonoursCustomParameterName(): void
    {
        $source = new UrlParamSource(
            request: $this->requestWithQuery(
                query: [
                    'locale' => 'en-US',
                ],
            ),
            parameterName: 'locale',
        );

        $detected = $source->detect();

        self::assertNotNull($detected);
        self::assertSame('en-US', $detected->tag);
    }

    public function testDetectsOncePerSourceInstance(): void
    {
        $source = new UrlParamSource(
            request: $this->requestWithQuery(
                query: [
                    'lang' => 'da-DK',
                ],
            ),
            parameterName: 'lang',
        );

        $first = $source->detect();

        self::assertNotNull($first);
        self::assertSame($first, $source->detect());
    }
}
