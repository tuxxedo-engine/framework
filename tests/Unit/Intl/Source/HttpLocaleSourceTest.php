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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Http\Request\Context\StubHeaderContext;
use Support\Http\Request\Context\StubInputContext;
use Support\Http\Request\Context\StubUploadedFilesContext;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Intl\Config\IntlConfig;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\Source\AcceptLanguageSource;
use Tuxxedo\Intl\Source\CookieSource;
use Tuxxedo\Intl\Source\HttpLocaleSource;
use Tuxxedo\Intl\Source\LocaleSourceInterface;
use Tuxxedo\Intl\Source\RouteArgumentSource;
use Tuxxedo\Intl\Source\UrlParamSource;
use Tuxxedo\Router\DispatchableRoute;
use Tuxxedo\Router\Route;

class HttpLocaleSourceTest extends TestCase
{
    /**
     * @param array<string, string> $routeArguments
     * @param array<string, string> $query
     * @param array<string, string> $cookies
     * @param list<string> $preferredLanguages
     */
    private function makeSource(
        array $routeArguments = [],
        array $query = [],
        array $cookies = [],
        array $preferredLanguages = [],
        ?string $routeArgument = 'language',
        ?string $queryParameter = 'lang',
        ?string $cookieName = 'lang',
        bool $acceptLanguage = true,
    ): HttpLocaleSource {
        $headers = new StubHeaderContext();
        $headers->preferredLanguages = $preferredLanguages;

        return new HttpLocaleSource(
            request: new Request(
                route: new DispatchableRoute(
                    route: new Route(
                        method: Method::GET,
                        path: '/{?language}/model',
                        controller: static::class,
                        action: 'index',
                    ),
                    arguments: $routeArguments,
                ),
                headers: $headers,
                cookies: new StubInputContext(
                    values: $cookies,
                ),
                get: new StubInputContext(
                    values: $query,
                ),
                post: new StubInputContext(),
                files: new StubUploadedFilesContext(),
            ),
            config: new IntlConfig(
                rootDirectory: '/unused',
                defaultLocale: Locale::parse('en-US'),
            ),
            routeArgument: $routeArgument,
            queryParameter: $queryParameter,
            cookieName: $cookieName,
            acceptLanguage: $acceptLanguage,
        );
    }

    public function testBuildsSourcesInPriorityOrder(): void
    {
        self::assertSame(
            [
                RouteArgumentSource::class,
                UrlParamSource::class,
                CookieSource::class,
                AcceptLanguageSource::class,
            ],
            \array_map(
                static fn (LocaleSourceInterface $source): string => $source::class,
                $this->makeSource()->sources,
            ),
        );
    }

    public function testSkipsDisabledSources(): void
    {
        $source = $this->makeSource(
            routeArgument: null,
            queryParameter: null,
            cookieName: null,
            acceptLanguage: false,
        );

        self::assertSame([], $source->sources);
        self::assertNull($source->detect());
    }

    /**
     * @return \Generator<string, array{array<string, string>, array<string, string>, array<string, string>, list<string>, string}>
     */
    public static function precedenceDataProvider(): \Generator
    {
        yield 'route argument wins' => [
            [
                'language' => 'da-DK',
            ],
            [
                'lang' => 'sv-SE',
            ],
            [
                'lang' => 'fi-FI',
            ],
            [
                'nb-NO',
            ],
            'da-DK',
        ];

        yield 'query parameter before cookie' => [
            [],
            [
                'lang' => 'sv-SE',
            ],
            [
                'lang' => 'fi-FI',
            ],
            [
                'nb-NO',
            ],
            'sv-SE',
        ];

        yield 'cookie before accept-language' => [
            [],
            [],
            [
                'lang' => 'fi-FI',
            ],
            [
                'nb-NO',
            ],
            'fi-FI',
        ];

        yield 'accept-language last' => [
            [],
            [],
            [],
            [
                'nb-NO',
            ],
            'nb-NO',
        ];

        yield 'unparseable values fall through' => [
            [
                'language' => '!!garbage!!',
            ],
            [
                'lang' => '!!garbage!!',
            ],
            [],
            [
                'is-IS',
            ],
            'is-IS',
        ];
    }

    /**
     * @param array<string, string> $routeArguments
     * @param array<string, string> $query
     * @param array<string, string> $cookies
     * @param list<string> $preferredLanguages
     */
    #[DataProvider('precedenceDataProvider')]
    public function testFirstDetectingSourceWins(
        array $routeArguments,
        array $query,
        array $cookies,
        array $preferredLanguages,
        string $expected,
    ): void {
        $detected = $this->makeSource(
            routeArguments: $routeArguments,
            query: $query,
            cookies: $cookies,
            preferredLanguages: $preferredLanguages,
        )->detect();

        self::assertNotNull($detected);
        self::assertSame($expected, $detected->tag);
    }

    public function testReturnsNullWhenNothingDetects(): void
    {
        self::assertNull($this->makeSource()->detect());
    }

    public function testHonoursCustomNames(): void
    {
        $detected = $this->makeSource(
            cookies: [
                'user_locale' => 'da-DK',
            ],
            cookieName: 'user_locale',
            acceptLanguage: false,
        )->detect();

        self::assertNotNull($detected);
        self::assertSame('da-DK', $detected->tag);
    }
}
