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

use App\ErrorHandlers\HttpErrorHandler;
use Tuxxedo\Application\ApplicationConfigurator;
use Tuxxedo\Application\Profile;
use Tuxxedo\Http\Kernel\ErrorHandlerInterface;

require_once __DIR__ . '/../vendor/autoload.php';

$appDirectory = __DIR__ . '/../app';

$builder = ApplicationConfigurator::createFromConfigDirectory($appDirectory . '/config')
    ->withDefaultRouter($appDirectory . '/Controllers')
    ->withServiceFile($appDirectory . '/services.php')
    ->withDefaultExceptionHandler(static fn (): ErrorHandlerInterface => new HttpErrorHandler());

if ($builder->appProfile === Profile::DEBUG) {
    $builder->withDebugHandler();
}

$builder->build()->run();
