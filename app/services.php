<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

use App\Services\Logger\Logger;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Session\Session;
use Tuxxedo\View\Lumi\LumiConfigurator;
use Tuxxedo\View\Lumi\LumiViewRender;
use Tuxxedo\View\ViewRenderInterface;

return static function (ContainerInterface $container): void {
    $container->bind(Logger::class);
    $container->bind(Session::class);

    $container->lazy(
        LumiViewRender::class,
        static function (ContainerInterface $container): ViewRenderInterface {
            return LumiConfigurator::fromConfig($container)
                ->allowAllFunctions()
                ->enableErrorReporting()
                ->build();
        },
    );
};
