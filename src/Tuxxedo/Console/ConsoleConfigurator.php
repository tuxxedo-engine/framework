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

use Tuxxedo\Application\AbstractConfigurator;
use Tuxxedo\Application\Environment;
use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Console\Config\HelpConfig;
use Tuxxedo\Console\Config\SuggestionPolicy;
use Tuxxedo\Console\Input\InputInterface;
use Tuxxedo\Console\Input\StdinInput;
use Tuxxedo\Console\Invocation\ArgvParser;
use Tuxxedo\Console\Invocation\ParameterBinder;
use Tuxxedo\Console\Kernel\CommandDiscoverer;
use Tuxxedo\Console\Kernel\CommandDispatcher;
use Tuxxedo\Console\Kernel\CommandRegistry;
use Tuxxedo\Console\Kernel\ConsoleErrorHandlerInterface;
use Tuxxedo\Console\Kernel\HelpFormatterInterface;
use Tuxxedo\Console\Kernel\Kernel;
use Tuxxedo\Console\Kernel\KernelInterface;
use Tuxxedo\Console\Middleware\CommandMiddlewareInterface;
use Tuxxedo\Console\Output\ConsoleOutput;
use Tuxxedo\Console\Output\OutputInterface;
use Tuxxedo\Console\Stream\PhpInputStream;
use Tuxxedo\Container\Container;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\File\FileCollectionFactory;
use Tuxxedo\File\FileException;

class ConsoleConfigurator extends AbstractConfigurator implements ConsoleConfiguratorInterface
{
    /**
     * @var list<class-string>
     */
    public private(set) array $commandClasses = [];

    public private(set) ?string $discoveryDirectory = null;
    public private(set) ?string $discoveryBaseNamespace = null;

    /**
     * @var array<class-string<\Throwable>, list<\Closure|ConsoleErrorHandlerInterface>>
     */
    public private(set) array $exceptionHandlers = [];

    /**
     * @var list<\Closure|ConsoleErrorHandlerInterface>
     */
    public private(set) array $defaultExceptionHandlers = [];

    /**
     * @var list<\Closure|CommandMiddlewareInterface>
     */
    public private(set) array $middleware = [];

    public private(set) string $appName = '';
    public private(set) string $appVersion = '';
    public private(set) Environment $appEnvironment = Environment::PRODUCTION;
    public private(set) ?SuggestionPolicy $suggestionPolicyOverride = null;
    public private(set) ?HelpConfig $helpConfigOverride = null;
    public private(set) ?HelpFormatterInterface $helpFormatter = null;
    public private(set) bool $appNameHeaderEnabled = false;
    public private(set) ?string $appNameHeaderLabel = null;

    public function __construct(
        ContainerInterface $container,
        ?ConfigInterface $config = null,
    ) {
        parent::__construct(
            config: $config,
            container: $container,
        );
    }

    public function withAppName(
        string $name,
    ): self {
        $this->appName = $name;

        return $this;
    }

    public function withAppVersion(
        string $version,
    ): self {
        $this->appVersion = $version;

        return $this;
    }

    public function withAppEnvironment(
        Environment $environment,
    ): self {
        $this->appEnvironment = $environment;

        return $this;
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

    public function withSuggestionPolicy(
        SuggestionPolicy $policy,
    ): self {
        $this->suggestionPolicyOverride = $policy;

        return $this;
    }

    public function withHelpConfig(
        HelpConfig $config,
    ): self {
        $this->helpConfigOverride = $config;

        return $this;
    }

    public function withHelpFormatter(
        HelpFormatterInterface $formatter,
    ): self {
        $this->helpFormatter = $formatter;

        return $this;
    }

    public function withAppNameHeader(
        ?string $label = null,
    ): self {
        $this->appNameHeaderEnabled = true;
        $this->appNameHeaderLabel = $label === '' || $label === null
            ? null
            : $label;

        return $this;
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

    public function build(): KernelInterface
    {
        $output = ConsoleOutput::createFromStandardStreams();
        $stdin = \fopen('php://stdin', 'rb');

        if ($stdin === false) {
            throw ConsoleException::fromStreamNotOpen(); // @codeCoverageIgnore
        }

        $input = new StdinInput(
            stream: new PhpInputStream($stdin),
            output: $output->stdout,
        );

        $container = $this->container ?? new Container();

        $container->singleton($output);
        $container->singleton($input);
        $container->singletonLazy(
            class: OutputInterface::class,
            initializer: static fn (): OutputInterface => $output->stdout,
        );

        $container->singletonLazy(
            class: InputInterface::class,
            initializer: static fn (): InputInterface => $input,
        );

        $container->singleton($this->appEnvironment);
        $container->singleton($this->suggestionPolicyOverride ?? SuggestionPolicy::default());
        $container->singleton($this->helpConfigOverride ?? HelpConfig::default());

        if ($this->helpFormatter !== null) {
            $container->singleton($this->helpFormatter);
        }

        foreach ($this->serviceFiles as $file) {
            $container->callFile($file);
        }

        $discoverer = new CommandDiscoverer(
            container: $container,
        );
        $descriptors = [];

        foreach ($this->collectClasses() as $class) {
            foreach ($discoverer->discover($class) as $descriptor) {
                $descriptors[] = $descriptor;
            }
        }

        $registry = new CommandRegistry($descriptors);

        $container->singleton($registry);

        $dispatcher = new CommandDispatcher(
            registry: $registry,
            parser: new ArgvParser(),
            binder: new ParameterBinder($container),
            container: $container,
        );

        $kernel = new Kernel(
            container: $container,
            dispatcher: $dispatcher,
            output: $output,
            appName: $this->appName,
            appNameHeaderEnabled: $this->appNameHeaderEnabled,
            appNameHeaderLabel: $this->appNameHeaderLabel,
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
}
