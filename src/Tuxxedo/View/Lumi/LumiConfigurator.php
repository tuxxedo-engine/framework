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
use Tuxxedo\View\Lumi\Optimizer\Dce\DceOptimizer;
use Tuxxedo\View\Lumi\Optimizer\OptimizerInterface;
use Tuxxedo\View\Lumi\Optimizer\Sccp\SccpOptimizer;
use Tuxxedo\View\Lumi\Parser\ParserInterface;
use Tuxxedo\View\Lumi\Runtime\Directive\DefaultDirectives;
use Tuxxedo\View\Lumi\Runtime\Directive\DirectivesInterface;
use Tuxxedo\View\Lumi\Runtime\Filter\DefaultFilters;
use Tuxxedo\View\Lumi\Runtime\Filter\FilterProviderInterface;
use Tuxxedo\View\Lumi\Runtime\Function\DefaultFunctions;
use Tuxxedo\View\Lumi\Runtime\Function\FunctionProviderInterface;
use Tuxxedo\View\Lumi\Runtime\Loader;
use Tuxxedo\View\Lumi\Runtime\LoaderInterface;
use Tuxxedo\View\Lumi\Runtime\Runtime;
use Tuxxedo\View\Lumi\Runtime\RuntimeFunctionPolicy;
use Tuxxedo\View\ViewRenderInterface;

class LumiConfigurator implements LumiConfiguratorInterface
{
    public private(set) string $viewDirectory = '';
    public private(set) string $viewExtension = '';
    public private(set) bool $viewAlwaysCompile = false;
    public private(set) bool $viewDisableErrorReporting = true;
    public private(set) string $viewCacheDirectory = '';

    public private(set) ?LexerInterface $lexer = null;
    public private(set) ?ParserInterface $parser = null;
    public private(set) ?CompilerInterface $compiler = null;

    public private(set) array $optimizers = [];

    public private(set) ?LoaderInterface $loader = null;

    public private(set) array $directives = [];
    public private(set) array $defaultDirectives = [];

    public private(set) array $functions = [];
    public private(set) array $customFunctions = [];

    public private(set) RuntimeFunctionPolicy $functionPolicy = RuntimeFunctionPolicy::CUSTOM_ONLY;
    public private(set) bool $withDefaultFunctions = true;
    public private(set) array $functionProviders = [];

    public private(set) array $instanceCallClasses = [];

    public private(set) array $customFilters = [];
    public private(set) bool $withDefaultFilters = true;
    public private(set) array $filterProviders = [];

    final public function __construct()
    {
        $this->optimizers = [
            SccpOptimizer::class => new SccpOptimizer(),
            DceOptimizer::class => new DceOptimizer(),
        ];

        $this->defaultDirectives = DefaultDirectives::defaults();
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

        if ($config->has($namespace . '.disableErrorReporting')) {
            if ($config->getBool($namespace . '.disableErrorReporting')) {
                $configurator->disableErrorReporting();
            } else {
                $configurator->enableErrorReporting();
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

    public function enableStripComments(): self
    {
        $this->defaultDirectives['lumi.compiler_strip_comments'] = true;

        return $this;
    }

    public function disableStripComments(): self
    {
        $this->defaultDirectives['lumi.compiler_strip_comments'] = false;

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

    public function enableErrorReporting(): self
    {
        $this->viewDisableErrorReporting = false;

        return $this;
    }

    public function disableErrorReporting(): self
    {
        $this->viewDisableErrorReporting = true;

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

        if ($this->functionPolicy === RuntimeFunctionPolicy::DISALLOW_ALL) {
            $this->functionPolicy = RuntimeFunctionPolicy::CUSTOM_ONLY;
        }

        return $this;
    }

    public function allowAllFunctions(): self
    {
        $this->functionPolicy = RuntimeFunctionPolicy::ALLOW_ALL;

        return $this;
    }

    public function disallowAllFunctions(): self
    {
        $this->functions = [];
        $this->customFunctions = [];
        $this->functionProviders = [];
        $this->functionPolicy = RuntimeFunctionPolicy::DISALLOW_ALL;
        $this->withDefaultFunctions = false;

        return $this;
    }

    /**
     * @param \Closure(array<mixed> $arguments, ViewRenderInterface $render, DirectivesInterface $directives): mixed $handler
     */
    public function defineFunction(
        string $name,
        \Closure $handler,
    ): self {
        $this->customFunctions[$name] = $handler;

        if ($this->functionPolicy === RuntimeFunctionPolicy::DISALLOW_ALL) {
            $this->functionPolicy = RuntimeFunctionPolicy::CUSTOM_ONLY;
        }

        return $this;
    }

    /**
     * @param \Closure(mixed $value, DirectivesInterface $directives): mixed $handler
     */
    public function defineFilter(
        string $name,
        \Closure $handler,
    ): self {
        $this->customFilters[$name] = $handler;

        return $this;
    }

    public function withDefaultFilters(): self
    {
        $this->withDefaultFilters = true;

        return $this;
    }

    public function withoutDefaultFilters(): self
    {
        $this->withDefaultFilters = false;

        return $this;
    }

    public function withFilterProvider(
        FilterProviderInterface $provider,
    ): LumiConfiguratorInterface {
        $this->filterProviders[] = $provider;

        return $this;
    }

    public function withDefaultFunctions(): self
    {
        $this->withDefaultFunctions = true;

        return $this;
    }

    public function withoutDefaultFunctions(): self
    {
        $this->withDefaultFunctions = false;

        return $this;
    }

    public function withFunctionProvider(
        FunctionProviderInterface $provider,
    ): self {
        $this->functionProviders[] = $provider;

        if ($this->functionPolicy === RuntimeFunctionPolicy::DISALLOW_ALL) {
            $this->functionPolicy = RuntimeFunctionPolicy::CUSTOM_ONLY;
        }

        return $this;
    }

    public function withAnyInstanceCall(): self
    {
        $this->instanceCallClasses = [];

        return $this;
    }

    /**
     * @param class-string $className
     */
    public function withAllowedInstanceCall(
        string ...$className,
    ): self {
        $this->instanceCallClasses = \array_merge(
            $this->instanceCallClasses,
            $className,
        );

        return $this;
    }

    public function withDefaultLexer(): self
    {
        $this->lexer = null;

        return $this;
    }

    public function useLexer(
        LexerInterface $lexer,
    ): self {
        $this->lexer = $lexer;

        return $this;
    }

    public function withDefaultParser(): self
    {
        $this->parser = null;

        return $this;
    }

    public function useParser(
        ParserInterface $parser,
    ): self {
        $this->parser = $parser;

        return $this;
    }

    public function withDefaultCompiler(): self
    {
        $this->compiler = null;

        return $this;
    }

    public function useCompiler(
        CompilerInterface $compiler,
    ): self {
        $this->compiler = $compiler;

        return $this;
    }

    public function withoutOptimizers(): self
    {
        $this->optimizers = [];

        return $this;
    }

    public function withSccpOptimizer(): self
    {
        $this->optimizers[SccpOptimizer::class] = new SccpOptimizer();

        return $this;
    }

    public function withoutSccpOptimizer(): self
    {
        unset($this->optimizers[SccpOptimizer::class]);

        return $this;
    }

    public function withDceOptimizer(): self
    {
        $this->optimizers[DceOptimizer::class] = new DceOptimizer();

        return $this;
    }

    public function withoutDceOptimizer(): self
    {
        unset($this->optimizers[DceOptimizer::class]);

        return $this;
    }

    public function withCustomOptimizer(
        OptimizerInterface $optimizer,
    ): self {
        $this->optimizers[$optimizer::class] = $optimizer;

        return $this;
    }

    public function withDefaultLoader(): self
    {
        $this->loader = null;

        return $this;
    }

    public function useLoader(
        LoaderInterface $loader,
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
        if (!\is_dir($this->viewDirectory)) {
            return false;
        }

        if (!\is_dir($this->viewCacheDirectory)) {
            return false;
        }

        return false;
    }

    /**
     * @return array<string, \Closure(array<mixed> $arguments, ViewRenderInterface $render, DirectivesInterface $directives): mixed>
     */
    private function loadFunctionProvider(
        FunctionProviderInterface $provider,
    ): array {
        $functions = [];

        /**
         * @var string $function
         * @var \Closure(array<mixed> $arguments, ViewRenderInterface $render, DirectivesInterface $directives): mixed $handler
         */
        foreach ($provider->export() as [$function, $handler]) {
            $functions[$function] = $handler;
        }

        return $functions;
    }

    /**
     * @return array<string, \Closure(mixed $value, DirectivesInterface $directives): mixed>
     */
    private function loadFilterProvider(
        FilterProviderInterface $provider,
    ): array {
        $filters = [];

        /**
         * @var string $filter
         * @var \Closure(mixed $value, DirectivesInterface $directives): mixed $handler
         */
        foreach ($provider->export() as [$filter, $handler]) {
            $filters[$filter] = $handler;
        }

        return $filters;
    }

    /**
     * @return array<string, \Closure(array<mixed> $arguments, ViewRenderInterface $render, DirectivesInterface $directives): mixed>
     */
    private function buildCustomFunctions(): array
    {
        $customFunctions = [];

        if ($this->withDefaultFunctions) {
            $customFunctions = $this->loadFunctionProvider(
                provider: new DefaultFunctions(),
            );
        }

        if (\sizeof($this->functionProviders) > 0) {
            $customFunctions = \array_merge(
                $customFunctions,
                ...\array_map(
                    fn (FunctionProviderInterface $provider): array => $this->loadFunctionProvider($provider),
                    $this->functionProviders,
                ),
            );
        }

        return $customFunctions;
    }

    /**
     * @return array<string, \Closure(mixed $value, DirectivesInterface $directives): mixed>
     */
    private function buildCustomFilters(): array
    {
        $customFilters = [];

        if ($this->withDefaultFilters) {
            $customFilters = $this->loadFilterProvider(
                provider: new DefaultFilters(),
            );
        }

        if (\sizeof($this->filterProviders) > 0) {
            $customFilters = \array_merge(
                $customFilters,
                ...\array_map(
                    fn (FilterProviderInterface $provider): array => $this->loadFilterProvider($provider),
                    $this->filterProviders,
                ),
            );
        }

        return $customFilters;
    }

    public function build(): ViewRenderInterface
    {
        return new LumiViewRender(
            engine: LumiEngine::createCustom(
                lexer: $this->lexer,
                parser: $this->parser,
                compiler: $this->compiler,
                optimizers: $this->optimizers,
            ),
            loader: $this->loader ?? new Loader(
                directory: $this->viewDirectory,
                cacheDirectory: $this->viewCacheDirectory,
                extension: $this->viewExtension,
            ),
            runtime: new Runtime(
                directives: \array_merge(
                    $this->directives,
                    $this->defaultDirectives,
                ),
                functions: $this->functions,
                customFunctions: \array_merge(
                    $this->buildCustomFunctions(),
                    $this->customFunctions,
                ),
                functionPolicy: $this->functionPolicy,
                instanceCallClasses: $this->instanceCallClasses,
                filters: \array_merge(
                    $this->buildCustomFilters(),
                    $this->customFilters,
                ),
            ),
            alwaysCompile: $this->viewAlwaysCompile,
            disableErrorReporting: $this->viewDisableErrorReporting,
        );
    }
}
