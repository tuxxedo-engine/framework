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

namespace Tuxxedo\Intl\Source;

use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Intl\Config\IntlConfigInterface;
use Tuxxedo\Intl\LocaleInterface;

class HttpLocaleSource extends AbstractMemoizedLocaleSource
{
    /**
     * @var list<LocaleSourceInterface>
     */
    public readonly array $sources;

    public function __construct(
        RequestInterface $request,
        IntlConfigInterface $config,
        ?string $routeArgument = null,
        ?string $queryParameter = null,
        ?string $cookieName = null,
        bool $acceptLanguage = true,
    ) {
        $sources = [];

        if ($routeArgument !== null) {
            $sources[] = new RouteArgumentSource(
                request: $request,
                argumentName: $routeArgument,
            );
        }

        if ($queryParameter !== null) {
            $sources[] = new UrlParamSource(
                request: $request,
                parameterName: $queryParameter,
            );
        }

        if ($cookieName !== null) {
            $sources[] = new CookieSource(
                request: $request,
                cookieName: $cookieName,
            );
        }

        if ($acceptLanguage) {
            $sources[] = new AcceptLanguageSource(
                request: $request,
                config: $config,
            );
        }

        $this->sources = $sources;
    }

    protected function resolve(): ?LocaleInterface
    {
        foreach ($this->sources as $source) {
            $locale = $source->detect();

            if ($locale !== null) {
                return $locale;
            }
        }

        return null;
    }
}
