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

namespace Tuxxedo\Console\Kernel;

use Tuxxedo\Application\Environment;
use Tuxxedo\Console\ExitCode;
use Tuxxedo\Console\Middleware\CommandMiddlewareInterface;
use Tuxxedo\Console\Output\ConsoleOutputInterface;
use Tuxxedo\Container\ContainerInterface;

interface KernelInterface
{
    public ContainerInterface $container {
        get;
    }

    public CommandDispatcherInterface $dispatcher {
        get;
    }

    public ConsoleOutputInterface $output {
        get;
    }

    public string $appName {
        get;
    }

    public string $appVersion {
        get;
    }

    public Environment $appEnvironment {
        get;
    }

    /**
     * @var list<(\Closure(): CommandMiddlewareInterface)|CommandMiddlewareInterface>
     */
    public array $middleware {
        get;
    }

    /**
     * @var array<class-string<\Throwable>, list<(\Closure(): ConsoleErrorHandlerInterface)|ConsoleErrorHandlerInterface>>
     */
    public array $exceptionHandlers {
        get;
    }

    /**
     * @var list<(\Closure(): ConsoleErrorHandlerInterface)|ConsoleErrorHandlerInterface>
     */
    public array $defaultExceptionHandlers {
        get;
    }




    /**
     * @param list<string> $argv
     */
    public function run(
        array $argv,
    ): ExitCode;
}
