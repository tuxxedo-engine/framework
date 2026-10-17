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
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Intl\Source\RouteArgumentSource;
use Tuxxedo\Router\DispatchableRoute;
use Tuxxedo\Router\Route;

class RouteArgumentSourceTest extends TestCase
{
    /**
     * @param array<string, string>|null $arguments
     */
    private function makeRequest(
        ?array $arguments,
    ): Request {
        return new Request(
            route: $arguments !== null
                ? new DispatchableRoute(
                    route: new Route(
                        method: Method::GET,
                        path: '/{?language}/model',
                        controller: static::class,
                        action: 'index',
                    ),
                    arguments: $arguments,
                )
                : null,
            headers: new StubHeaderContext(),
            cookies: new StubInputContext(),
            get: new StubInputContext(),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
        );
    }

    public function testReturnsNullWithoutRoute(): void
    {
        $source = new RouteArgumentSource(
            request: $this->makeRequest(
                arguments: null,
            ),
            argumentName: 'language',
        );

        self::assertNull($source->detect());
    }

    public function testReturnsNullWhenArgumentMissing(): void
    {
        $source = new RouteArgumentSource(
            request: $this->makeRequest(
                arguments: [],
            ),
            argumentName: 'language',
        );

        self::assertNull($source->detect());
    }

    public function testReturnsNullWhenOptionalArgumentMatchedEmpty(): void
    {
        $source = new RouteArgumentSource(
            request: $this->makeRequest(
                arguments: [
                    'language' => '',
                ],
            ),
            argumentName: 'language',
        );

        self::assertNull($source->detect());
    }

    public function testNormalisesLowerCaseArgument(): void
    {
        $source = new RouteArgumentSource(
            request: $this->makeRequest(
                arguments: [
                    'language' => 'cs-cz',
                ],
            ),
            argumentName: 'language',
        );

        self::assertSame('cs-CZ', $source->detect()?->tag);
    }

    public function testReturnsNullWhenArgumentUnparseable(): void
    {
        $source = new RouteArgumentSource(
            request: $this->makeRequest(
                arguments: [
                    'language' => 'not-a-locale',
                ],
            ),
            argumentName: 'language',
        );

        self::assertNull($source->detect());
    }

    public function testHonoursCustomArgumentName(): void
    {
        $source = new RouteArgumentSource(
            request: $this->makeRequest(
                arguments: [
                    'locale' => 'da-DK',
                ],
            ),
            argumentName: 'locale',
        );

        self::assertSame('da-DK', $source->detect()?->tag);
    }
}
