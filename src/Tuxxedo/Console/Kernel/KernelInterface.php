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

    /**
     * @var array<\Closure(): CommandMiddlewareInterface>
     */
    public array $middleware {
        get;
    }

    /**
     * @var array<class-string<\Throwable>, array<\Closure(): ConsoleErrorHandlerInterface>>
     */
    public array $exceptionHandlers {
        get;
    }

    /**
     * @var array<\Closure(): ConsoleErrorHandlerInterface>
     */
    public array $defaultExceptionHandlers {
        get;
    }

    /**
     * @param (\Closure(): CommandMiddlewareInterface)|CommandMiddlewareInterface $middleware
     */
    public function middleware(
        \Closure|CommandMiddlewareInterface $middleware,
    ): static;

    /**
     * @param class-string<\Throwable> $exceptionClass
     * @param (\Closure(): ConsoleErrorHandlerInterface)|ConsoleErrorHandlerInterface $handler
     */
    public function whenException(
        string $exceptionClass,
        \Closure|ConsoleErrorHandlerInterface $handler,
    ): static;

    /**
     * @param (\Closure(): ConsoleErrorHandlerInterface)|ConsoleErrorHandlerInterface $handler
     */
    public function defaultExceptionHandler(
        \Closure|ConsoleErrorHandlerInterface $handler,
    ): static;

    /**
     * @param list<string> $argv
     */
    public function run(
        array $argv,
    ): ExitCode;
}
