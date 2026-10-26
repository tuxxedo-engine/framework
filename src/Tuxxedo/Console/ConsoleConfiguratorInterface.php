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

use Tuxxedo\Application\AbstractConfiguratorInterface;
use Tuxxedo\Application\Environment;
use Tuxxedo\Console\Config\HelpConfig;
use Tuxxedo\Console\Config\SuggestionPolicy;
use Tuxxedo\Console\Kernel\ConsoleErrorHandlerInterface;
use Tuxxedo\Console\Kernel\HelpFormatterInterface;
use Tuxxedo\Console\Kernel\KernelInterface;
use Tuxxedo\Console\Middleware\CommandMiddlewareInterface;

interface ConsoleConfiguratorInterface extends AbstractConfiguratorInterface
{
    public ?string $discoveryDirectory {
        get;
    }

    public ?string $discoveryBaseNamespace {
        get;
    }

    /**
     * @var list<class-string>
     */
    public array $commandClasses {
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

    public ?SuggestionPolicy $suggestionPolicyOverride {
        get;
    }

    public ?HelpConfig $helpConfigOverride {
        get;
    }

    public ?HelpFormatterInterface $helpFormatter {
        get;
    }

    public bool $appNameHeaderEnabled {
        get;
    }

    public ?string $appNameHeaderLabel {
        get;
    }

    /**
     * @var list<\Closure|CommandMiddlewareInterface>
     */
    public array $middleware {
        get;
    }

    /**
     * @var array<class-string<\Throwable>, list<\Closure|ConsoleErrorHandlerInterface>>
     */
    public array $exceptionHandlers {
        get;
    }

    /**
     * @var list<\Closure|ConsoleErrorHandlerInterface>
     */
    public array $defaultExceptionHandlers {
        get;
    }

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

    public function withAppName(
        string $name,
    ): self;

    public function withAppVersion(
        string $version,
    ): self;

    public function withAppEnvironment(
        Environment $environment,
    ): self;

    public function withSuggestionPolicy(
        SuggestionPolicy $policy,
    ): self;

    public function withHelpConfig(
        HelpConfig $config,
    ): self;

    public function withHelpFormatter(
        HelpFormatterInterface $formatter,
    ): self;

    public function withAppNameHeader(
        ?string $label = null,
    ): self;

    /**
     * @throws ConsoleException
     */
    public function build(): KernelInterface;
}
