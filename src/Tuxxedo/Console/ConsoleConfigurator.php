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

use Tuxxedo\Console\Discovery\CommandDiscoverer;
use Tuxxedo\Console\Invocation\ArgvParser;
use Tuxxedo\Console\Invocation\ParameterBinder;
use Tuxxedo\Console\Kernel\CommandDispatcher;
use Tuxxedo\Console\Kernel\ConsoleErrorHandlerInterface;
use Tuxxedo\Console\Kernel\Kernel;
use Tuxxedo\Console\Kernel\KernelInterface;
use Tuxxedo\Console\Middleware\CommandMiddlewareInterface;
use Tuxxedo\Console\Output\ConsoleOutput;
use Tuxxedo\Console\Output\OutputInterface;
use Tuxxedo\Console\Registry\CommandRegistry;
use Tuxxedo\Container\Container;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\File\FileCollectionFactory;
use Tuxxedo\File\FileException;

class ConsoleConfigurator implements ConsoleConfiguratorInterface
{
    /**
     * @var list<class-string>
     */
    private array $commandClasses = [];

    /**
     * @var list<string>
     */
    private array $serviceFiles = [];

    private ?string $discoveryDirectory = null;
    private ?string $discoveryBaseNamespace = null;

    /**
     * @var array<class-string<\Throwable>, list<\Closure|ConsoleErrorHandlerInterface>>
     */
    private array $exceptionHandlers = [];

    /**
     * @var list<\Closure|ConsoleErrorHandlerInterface>
     */
    private array $defaultExceptionHandlers = [];

    /**
     * @var list<\Closure|CommandMiddlewareInterface>
     */
    private array $middleware = [];

    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    public static function create(
        ?ContainerInterface $container = null,
    ): self {
        return new self(
            container: $container ?? new Container(),
        );
    }

    public function withDefaultCommandDiscovery(
        string $directory,
        string $baseNamespace,
    ): self {
        $this->discoveryDirectory = $directory;
        $this->discoveryBaseNamespace = $baseNamespace;

        return $this;
    }

    public function withCommandClass(
        string $class,
    ): self {
        $this->commandClasses[] = $class;

        return $this;
    }

    public function withoutCommandClasses(): self
    {
        $this->commandClasses = [];
        $this->discoveryDirectory = null;
        $this->discoveryBaseNamespace = null;

        return $this;
    }

    public function withServiceFile(
        string $file,
    ): self {
        $this->serviceFiles[] = $file;

        return $this;
    }

    public function withExceptionHandler(
        string $exceptionClass,
        \Closure|ConsoleErrorHandlerInterface $handler,
    ): self {
        $this->exceptionHandlers[$exceptionClass] ??= [];
        $this->exceptionHandlers[$exceptionClass][] = $handler;

        return $this;
    }

    public function withoutExceptionHandlers(): self
    {
        $this->exceptionHandlers = [];

        return $this;
    }

    public function withDefaultExceptionHandler(
        \Closure|ConsoleErrorHandlerInterface $handler,
    ): self {
        $this->defaultExceptionHandlers[] = $handler;

        return $this;
    }

    public function withoutDefaultExceptionHandlers(): self
    {
        $this->defaultExceptionHandlers = [];

        return $this;
    }

    public function withMiddleware(
        \Closure|CommandMiddlewareInterface $middleware,
    ): self {
        $this->middleware[] = $middleware;

        return $this;
    }

    public function withoutMiddleware(): self
    {
        $this->middleware = [];

        return $this;
    }

    public function build(): KernelInterface
    {
        $output = ConsoleOutput::createFromStandardStreams();

        $this->container->singleton($output);
        $this->container->singletonLazy(
            class: OutputInterface::class,
            initializer: static fn (): OutputInterface => $output->stdout,
        );

        foreach ($this->serviceFiles as $file) {
            $this->container->callFile($file);
        }

        $discoverer = new CommandDiscoverer();
        $descriptors = [];

        foreach ($this->collectClasses() as $class) {
            foreach ($discoverer->discover($class) as $descriptor) {
                $descriptors[] = $descriptor;
            }
        }

        $dispatcher = new CommandDispatcher(
            registry: new CommandRegistry(commands: $descriptors),
            parser: new ArgvParser(),
            binder: new ParameterBinder(container: $this->container),
            container: $this->container,
        );

        $kernel = new Kernel(
            container: $this->container,
            dispatcher: $dispatcher,
            output: $output,
        );

        foreach ($this->exceptionHandlers as $exceptionClass => $handlers) {
            foreach ($handlers as $handler) {
                $kernel->whenException($exceptionClass, $handler);
            }
        }

        foreach ($this->defaultExceptionHandlers as $handler) {
            $kernel->defaultExceptionHandler($handler);
        }

        foreach ($this->middleware as $middleware) {
            $kernel->middleware($middleware);
        }

        return $kernel;
    }

    /**
     * @return list<class-string>
     */
    private function collectClasses(): array
    {
        $classes = $this->commandClasses;

        if ($this->discoveryDirectory === null) {
            return $classes;
        }

        try {
            $paths = FileCollectionFactory::paths(
                directory: $this->discoveryDirectory,
                pattern: '**/*.php',
            );
        } catch (FileException $exception) {
            throw ConsoleException::fromDiscoveryDirectoryNotFound(
                directory: $this->discoveryDirectory,
                previous: $exception,
            );
        }

        $baseNamespace = \rtrim($this->discoveryBaseNamespace ?? '', '\\');
        $resolvedDirectory = \realpath($this->discoveryDirectory);
        $normalizedDirectory = \str_replace(
            '\\',
            '/',
            $resolvedDirectory !== false
                ? $resolvedDirectory
                : $this->discoveryDirectory,
        );

        foreach ($paths as $path) {
            $suffix = \str_replace(
                [
                    $normalizedDirectory . '/',
                    '.php',
                    '/',
                ],
                [
                    '',
                    '',
                    '\\',
                ],
                $path,
            );

            /** @var class-string $className */
            $className = $baseNamespace . '\\' . $suffix;
            $classes[] = $className;
        }

        return $classes;
    }
}
