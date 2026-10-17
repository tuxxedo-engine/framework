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

use App\Subscribers\UserSubscriber;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Event\EventsManagerInterface;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Intl\Config\IntlConfigInterface;
use Tuxxedo\Intl\Resolver\LocaleResolverInterface;
use Tuxxedo\Intl\Resolver\NegotiatingLocaleResolver;
use Tuxxedo\Intl\Source\AcceptLanguageSource;
use Tuxxedo\Intl\Source\CookieSource;
use Tuxxedo\Intl\Source\LocaleSourceInterface;
use Tuxxedo\Intl\Source\LocaleSourceRegistryInterface;
use Tuxxedo\Intl\Source\RouteArgumentSource;
use Tuxxedo\Intl\Source\SourcePriority;
use Tuxxedo\Intl\Source\UrlParamSource;
use Tuxxedo\Logger\LoggerInterface;
use Tuxxedo\Logger\StreamLogger;

return static function (
    ContainerInterface $container,
    EventsManagerInterface $eventsManager,
    LocaleSourceRegistryInterface $localeSources,
): void {
    $container->singletonLazy(
        StreamLogger::class,
        static fn (): LoggerInterface => StreamLogger::createFromFile(
            file: __DIR__ . '/file.log',
        ),
    );

    $eventsManager->registerSubscriber(UserSubscriber::class);

    $container->singletonLazy(
        LocaleResolverInterface::class,
        static fn (ContainerInterface $container): LocaleResolverInterface => $container->resolve(NegotiatingLocaleResolver::class),
    );

    $localeSources->register(
        priority: SourcePriority::HIGHEST,
        source: static fn (): LocaleSourceInterface => new RouteArgumentSource(
            request: $container->resolve(RequestInterface::class),
            argumentName: 'language',
        ),
    );

    $localeSources->register(
        priority: SourcePriority::HIGH,
        source: static fn (): LocaleSourceInterface => new UrlParamSource(
            request: $container->resolve(RequestInterface::class),
            parameterName: 'lang',
        ),
    );

    $localeSources->register(
        priority: SourcePriority::NORMAL,
        source: static fn (): LocaleSourceInterface => new CookieSource(
            request: $container->resolve(RequestInterface::class),
            cookieName: 'lang',
        ),
    );

    $localeSources->register(
        priority: SourcePriority::LOW,
        source: static fn (): LocaleSourceInterface => new AcceptLanguageSource(
            request: $container->resolve(RequestInterface::class),
            config: $container->resolve(IntlConfigInterface::class),
        ),
    );
};
