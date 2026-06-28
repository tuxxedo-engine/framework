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

use App\Support\HttpErrorHandler;
use Tuxxedo\Application\ApplicationConfigurator;
use Tuxxedo\Application\Profile;
use Tuxxedo\Env\Env;
use Tuxxedo\Env\Source\DotEnvSource;
use Tuxxedo\Env\Source\ProcessEnvSource;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Kernel\ErrorHandlerInterface;
use Tuxxedo\View\Lumi\LumiConfiguratorInterface;

require_once __DIR__ . '/../vendor/autoload.php';

$appDirectory = __DIR__ . '/../app';
$envFile = $appDirectory . '/.env.dev';

if (\is_file($envFile)) {
    $env = new Env(
        new DotEnvSource(
            file: $envFile,
        ),
        new ProcessEnvSource(),
    );
} else {
    $env = new Env(
        new ProcessEnvSource(),
    );
}

$builder = ApplicationConfigurator::createFromConfigDirectory(
    directory: $appDirectory . '/config',
    env: $env,
)
    ->withDefaultRouter($appDirectory . '/Controllers')
    ->withDefaultLumi(
        static fn (LumiConfiguratorInterface $lumi): LumiConfiguratorInterface => $lumi
            ->allowFunction('php_sapi_name')
            ->allowFunction('php_uname')
            ->allowFunction('printf')
            ->allowFunction('acos')
            ->allowFunction('strval')
            ->disableErrorReporting(),
    )
    ->withDefaultConnectionManager()
    ->withServiceFile($appDirectory . '/services.php')
    ->withExceptionHandler(
        HttpException::class,
        static fn (): ErrorHandlerInterface => new HttpErrorHandler(),
    );

if ($builder->appProfile === Profile::DEBUG) {
    $builder->withDebugHandler();
}

$builder->build()->run();
