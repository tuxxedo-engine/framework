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
use Tuxxedo\Application\Profile;
use Tuxxedo\Debug\DebugErrorHandler;
use Tuxxedo\Http\Kernel\ErrorHandlerInterface;
use Tuxxedo\Http\Kernel\Kernel;
use Tuxxedo\Router\DynamicRouter;
use Tuxxedo\Session\Session;
use Tuxxedo\View\Engine\ViewRender;

require_once __DIR__ . '/../vendor/autoload.php';

$app = Kernel::createFromDirectory(
    directory: __DIR__ . '/../app',
);

if ($app->appProfile === Profile::DEBUG) {
    $app->defaultExceptionHandler(
        static fn (): ErrorHandlerInterface => new DebugErrorHandler(
            registerPhpErrorHandler: true,
        ),
    );
}

$app->container->bind(Logger::class);
$app->container->bind(Session::class);
$app->container->bind(ViewRender::class);

$app->router(
    DynamicRouter::createFromDirectory(
        container: $app->container,
        directory: __DIR__ . '/../app/Controllers',
        baseNamespace: '\App\Controllers\\',
        strictMode: true,
    ),
);

$app->run();
