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

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Database\ConnectionManager;
use Tuxxedo\Logger\LoggerInterface;
use Tuxxedo\Logger\StreamLogger;
use Tuxxedo\View\Lumi\LumiConfigurator;
use Tuxxedo\View\Lumi\LumiViewRender;
use Tuxxedo\View\ViewRenderInterface;

return static function (ContainerInterface $container): void {
    $container->persistent(ConnectionManager::class);

    $container->persistentLazy(
        LumiViewRender::class,
        static function (ContainerInterface $container): ViewRenderInterface {
            return LumiConfigurator::fromConfig($container)
                ->allowFunction('php_sapi_name')
                ->allowFunction('php_uname')
                ->allowFunction('printf')
                ->allowFunction('acos')
                ->allowFunction('strval')
                ->disableErrorReporting()
                ->build();
        },
    );

    $container->persistentLazy(
        StreamLogger::class,
        static fn (): LoggerInterface => StreamLogger::createFromFile(
            file: __DIR__ . '/file.log',
        ),
    );
};
