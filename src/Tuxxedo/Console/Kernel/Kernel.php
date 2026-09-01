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
use Tuxxedo\Console\ExitCodeExceptionInterface;
use Tuxxedo\Console\Middleware\CommandInvocationInterface;
use Tuxxedo\Console\Middleware\CommandMiddlewareInterface;
use Tuxxedo\Console\Middleware\MiddlewareNode;
use Tuxxedo\Console\Output\Color;
use Tuxxedo\Console\Output\ConsoleOutputInterface;
use Tuxxedo\Console\PrefersExitCodeInterface;
use Tuxxedo\Container\ContainerInterface;

class Kernel implements KernelInterface
{
    private const MAX_PREVIOUS_DEPTH = 5;

    public private(set) array $middleware = [];
    public private(set) array $exceptionHandlers = [];
    public private(set) array $defaultExceptionHandlers = [];

    public function __construct(
        public readonly ContainerInterface $container,
        public readonly CommandDispatcherInterface $dispatcher,
        public readonly ConsoleOutputInterface $output,
    ) {
    }

    public function middleware(
        \Closure|CommandMiddlewareInterface $middleware,
    ): static {
        if (!$middleware instanceof \Closure) {
            $middleware = static fn (): CommandMiddlewareInterface => $middleware;
        }

        $this->middleware[] = $middleware;

        return $this;
    }

    public function whenException(
        string $exceptionClass,
        \Closure|ConsoleErrorHandlerInterface $handler,
    ): static {
        if (!$handler instanceof \Closure) {
            $handler = static fn (): ConsoleErrorHandlerInterface => $handler;
        }

        $this->exceptionHandlers[$exceptionClass] ??= [];
        $this->exceptionHandlers[$exceptionClass][] = $handler;

        return $this;
    }

    public function defaultExceptionHandler(
        \Closure|ConsoleErrorHandlerInterface $handler,
    ): static {
        if (!$handler instanceof \Closure) {
            $handler = static fn (): ConsoleErrorHandlerInterface => $handler;
        }

        $this->defaultExceptionHandlers[] = $handler;

        return $this;
    }

    public function run(
        array $argv,
    ): ExitCode {
        /** @var list<string> $tail */
        $tail = \array_slice($argv, 1);

        try {
            $invocation = $this->dispatcher->resolve($tail);

            return $this->pipeline($invocation);
        } catch (\Throwable $exception) {
            return $this->handleException($exception);
        }
    }

    private function pipeline(
        CommandInvocationInterface $invocation,
    ): ExitCode {
        $next = new DispatchNode(
            dispatcher: $this->dispatcher,
        );

        foreach (\array_reverse($this->middleware) as $middleware) {
            $next = new MiddlewareNode(
                container: $this->container,
                current: $middleware,
                next: $next,
            );
        }

        return $next->handle(
            invocation: $invocation,
            next: $next,
        );
    }

    private function handleException(
        \Throwable $e,
    ): ExitCode {
        $handlers = [];

        if (\array_key_exists($e::class, $this->exceptionHandlers)) {
            $handlers = $this->exceptionHandlers[$e::class];
        }

        $handlers = \array_merge($handlers, $this->defaultExceptionHandlers);

        if ($e instanceof ExitCodeExceptionInterface) {
            $exitCode = $e->handleAsError($this->output);
        } else {
            $exception = $e;
            $preferredException = $e instanceof PrefersExitCodeInterface
                ? $e
                : null;

            while ($exception->getPrevious() !== null) {
                $exception = $exception->getPrevious();

                if ($exception instanceof ExitCodeExceptionInterface) {
                    $exitCode = $exception->handleAsError($this->output);

                    break;
                }

                if (
                    $preferredException === null &&
                    $exception instanceof PrefersExitCodeInterface
                ) {
                    $preferredException = $exception;
                }
            }

            if (!isset($exitCode)) {
                $exitCode = $preferredException !== null && $preferredException->exitCode !== null
                    ? $preferredException->exitCode
                    : ExitCode::FAILURE;

                $this->renderException($e);
            }
        }

        foreach ($handlers as $handler) {
            $exitCode = $this->container->call($handler)->handle(
                exception: $e,
                output: $this->output,
                exitCode: $exitCode,
            );
        }

        return $exitCode;
    }

    private function renderException(
        \Throwable $exception,
    ): void {
        $stderr = $this->output->stderr;
        $cwd = \getcwd();

        $stderr->line(
            \sprintf(
                'An unhandled exception occurred: \\%s: %s',
                $exception::class,
                $exception->getMessage(),
            ),
            Color::LIGHT_RED,
        );
        $stderr->line(
            '  at ' . $this->relativizePath($exception->getFile(), $cwd) . ':' . $exception->getLine(),
            Color::LIGHT_BLACK,
        );

        $previous = $exception->getPrevious();
        $depth = 0;

        while ($previous !== null) {
            if ($depth >= self::MAX_PREVIOUS_DEPTH) {
                $remaining = 0;
                $next = $previous;

                while ($next !== null) {
                    $remaining++;
                    $next = $next->getPrevious();
                }

                $stderr->line();
                $stderr->line(
                    \sprintf(
                        '  … (%d more causes)',
                        $remaining,
                    ),
                    Color::LIGHT_BLACK,
                );

                break;
            }

            $stderr->line();
            $stderr->line(
                \sprintf(
                    'Caused by \\%s: %s',
                    $previous::class,
                    $previous->getMessage(),
                ),
                Color::LIGHT_YELLOW,
            );
            $stderr->line(
                '  at ' . $this->relativizePath($previous->getFile(), $cwd) . ':' . $previous->getLine(),
                Color::LIGHT_BLACK,
            );

            $previous = $previous->getPrevious();
            $depth++;
        }
    }

    private function relativizePath(
        string $path,
        string|false $cwd,
    ): string {
        if ($cwd === false) {
            return $path;
        }

        $normalizedPath = \str_replace('\\', '/', $path);
        $normalizedCwd = \str_replace('\\', '/', $cwd);

        if (\str_starts_with($normalizedPath, $normalizedCwd . '/')) {
            return \substr($normalizedPath, \strlen($normalizedCwd) + 1);
        }

        return $path;
    }
}
