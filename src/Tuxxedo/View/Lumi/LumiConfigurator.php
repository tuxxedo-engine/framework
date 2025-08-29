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

namespace Tuxxedo\View\Lumi;

use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\View\Lumi\Compiler\CompilerInterface;
use Tuxxedo\View\Lumi\Lexer\LexerInterface;
use Tuxxedo\View\Lumi\Parser\ParserInterface;
use Tuxxedo\View\Lumi\Runtime\LumiDefaultFunctions;
use Tuxxedo\View\Lumi\Runtime\LumiDirectivesInterface;
use Tuxxedo\View\Lumi\Runtime\LumiLoader;
use Tuxxedo\View\Lumi\Runtime\LumiLoaderInterface;
use Tuxxedo\View\Lumi\Runtime\LumiRuntime;
use Tuxxedo\View\Lumi\Runtime\LumiRuntimeFunctionMode;
use Tuxxedo\View\ViewRenderInterface;

class LumiConfigurator implements LumiConfiguratorInterface
{
    public private(set) string $viewDirectory = '';
    public private(set) string $viewExtension = '';
    public private(set) bool $viewAlwaysCompile = false;
    public private(set) string $viewCacheDirectory = '';

    public private(set) ?LexerInterface $lexer = null;
    public private(set) ?ParserInterface $parser = null;
    public private(set) ?CompilerInterface $compiler = null;
    public private(set) ?LumiLoaderInterface $loader = null;

    public private(set) array $directives = [];
    public private(set) array $defaultDirectives = [
        'lumi.autoescape' => true,
    ];

    public private(set) array $functions = [];
    public private(set) array $customFunctions = [];
    public private(set) LumiRuntimeFunctionMode $functionMode = LumiRuntimeFunctionMode::CUSTOM_ONLY;

    final public function __construct()
    {
    }

    public static function fromConfig(
        ContainerInterface $container,
        string $namespace = 'view',
    ): static {
        $configurator = new static();
        $config = $container->resolve(ConfigInterface::class);

        if ($config->has($namespace . '.directory')) {
            $configurator->viewDirectory(
                directory: $config->getString($namespace . '.directory'),
            );
        }

        if ($config->has($namespace . '.cacheDirectory')) {
            $configurator->cacheDirectory(
                directory: $config->getString($namespace . '.cacheDirectory'),
            );
        }

        if ($config->has($namespace . '.extension')) {
            $configurator->viewExtension(
                extension: $config->getString($namespace . '.extension'),
            );
        }

        if ($config->has($namespace . '.alwaysCompile')) {
            if ($config->getBool($namespace . '.alwaysCompile')) {
                $configurator->enableAlwaysCompile();
            } else {
                $configurator->disableAlwaysCompile();
            }
        }

        return $configurator;
    }

    public function viewDirectory(
        string $directory,
    ): self {
        $this->viewDirectory = $directory;

        return $this;
    }

    public function viewExtension(
        string $extension,
    ): self {
        $this->viewExtension = $extension;

        return $this;
    }

    public function enableAutoescape(): self
    {
        $this->defaultDirectives['lumi.autoescape'] = true;

        return $this;
    }

    public function disableAutoescape(): self
    {
        $this->defaultDirectives['lumi.autoescape'] = false;

        return $this;
    }

    public function enableAlwaysCompile(): self
    {
        $this->viewAlwaysCompile = true;

        return $this;
    }

    public function disableAlwaysCompile(): self
    {
        $this->viewAlwaysCompile = false;

        return $this;
    }

    public function cacheDirectory(
        string $directory,
    ): self {
        $this->viewCacheDirectory = $directory;

        return $this;
    }

    public function allowFunction(
        string $name,
    ): self {
        $this->functions[] = $name;

        if ($this->functionMode === LumiRuntimeFunctionMode::DISALLOW_ALL) {
            $this->functionMode = LumiRuntimeFunctionMode::CUSTOM_ONLY;
        }

        return $this;
    }

    public function allowAllFunctions(): self
    {
        $this->functionMode = LumiRuntimeFunctionMode::ALLOW_ALL;

        return $this;
    }

    public function disallowAllFunctions(): self
    {
        $this->functions = [];
        $this->customFunctions = [];
        $this->functionMode = LumiRuntimeFunctionMode::DISALLOW_ALL;

        return $this;
    }

    /**
     * @param \Closure(array<mixed> $arguments, ViewRenderInterface $render, LumiDirectivesInterface $directives): mixed $handler
     */
    public function defineFunction(
        string $name,
        \Closure $handler,
    ): self {
        $this->customFunctions[$name] = $handler;

        if ($this->functionMode === LumiRuntimeFunctionMode::DISALLOW_ALL) {
            $this->functionMode = LumiRuntimeFunctionMode::CUSTOM_ONLY;
        }

        return $this;
    }

    /**
     * @param \Closure(mixed $value, LumiDirectivesInterface $directives): mixed $handler
     */
    public function defineFilter(
        string $name,
        \Closure $handler,
    ): self {
        // @todo

        return $this;
    }

    public function withoutDefaultFilters(): self
    {
        // @todo

        return $this;
    }

    public function withoutDefaultFunctions(): self
    {
        // @todo

        return $this;
    }

    public function useLexer(
        LexerInterface $lexer,
    ): self {
        $this->lexer = $lexer;

        return $this;
    }

    public function useParser(
        ParserInterface $parser,
    ): self {
        $this->parser = $parser;

        return $this;
    }

    public function useCompiler(
        CompilerInterface $compiler,
    ): self {
        $this->compiler = $compiler;

        return $this;
    }

    public function useLoader(
        LumiLoaderInterface $loader,
    ): self {
        $this->loader = $loader;

        return $this;
    }

    public function declare(
        string $name,
        string|int|float|bool|null $value,
    ): self {
        $this->directives[$name] = $value;

        return $this;
    }

    public function validate(): bool
    {
        // @todo

        return false;
    }

    public function build(): ViewRenderInterface
    {
        if ($this->functionMode !== LumiRuntimeFunctionMode::DISALLOW_ALL) {
            $customFunctions = [];

            /**
             * @var string $function
             * @var \Closure(array<mixed> $arguments, ViewRenderInterface $render, LumiDirectivesInterface $directives): mixed $handler
             */
            foreach ((new LumiDefaultFunctions()->export()) as [$function, $handler]) {
                $customFunctions[$function] = $handler;
            }

            $customFunctions = \array_merge(
                $customFunctions,
                $this->customFunctions,
            );
        }

        return new LumiViewRender(
            engine: LumiEngine::createCustom(
                lexer: $this->lexer,
                parser: $this->parser,
                compiler: $this->compiler,
            ),
            loader: $this->loader ?? new LumiLoader(
                directory: $this->viewDirectory,
                cacheDirectory: $this->viewCacheDirectory,
                extension: $this->viewExtension,
            ),
            runtime: new LumiRuntime(
                directives: \array_merge(
                    $this->directives,
                    $this->defaultDirectives,
                ),
                functions: $this->functions,
                customFunctions: $customFunctions ?? $this->customFunctions,
                functionMode: $this->functionMode,
            ),
            alwaysCompile: $this->viewAlwaysCompile,
        );
    }
}
