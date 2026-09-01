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

namespace Tuxxedo\Console;

use Tuxxedo\Console\Kernel\ConsoleErrorHandlerInterface;
use Tuxxedo\Console\Kernel\KernelInterface;
use Tuxxedo\Console\Middleware\CommandMiddlewareInterface;

interface ConsoleConfiguratorInterface
{
    public function withDefaultCommandDiscovery(
        string $directory,
        string $baseNamespace,
    ): self;

    /**
     * @param class-string $class
     */
    public function withCommandClass(
        string $class,
    ): self;

    public function withoutCommandClasses(): self;

    public function withServiceFile(
        string $file,
    ): self;

    /**
     * @param class-string<\Throwable> $exceptionClass
     * @param (\Closure(): ConsoleErrorHandlerInterface)|ConsoleErrorHandlerInterface $handler
     */
    public function withExceptionHandler(
        string $exceptionClass,
        \Closure|ConsoleErrorHandlerInterface $handler,
    ): self;

    public function withoutExceptionHandlers(): self;

    /**
     * @param (\Closure(): ConsoleErrorHandlerInterface)|ConsoleErrorHandlerInterface $handler
     */
    public function withDefaultExceptionHandler(
        \Closure|ConsoleErrorHandlerInterface $handler,
    ): self;

    public function withoutDefaultExceptionHandlers(): self;

    /**
     * @param (\Closure(): CommandMiddlewareInterface)|CommandMiddlewareInterface $middleware
     */
    public function withMiddleware(
        \Closure|CommandMiddlewareInterface $middleware,
    ): self;

    public function withoutMiddleware(): self;

    /**
     * @throws ConsoleException
     */
    public function build(): KernelInterface;
}
