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

namespace Tuxxedo\View\Lumi;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Reflection\MethodReflector;
use Tuxxedo\View\Lumi\Compiler\Compiler;
use Tuxxedo\View\Lumi\Compiler\CompilerState;
use Tuxxedo\View\Lumi\Config\LumiConfigInterface;
use Tuxxedo\View\Lumi\Highlight\Highlighter;
use Tuxxedo\View\Lumi\Highlight\Theme\ThemeFactory;
use Tuxxedo\View\Lumi\Highlight\Theme\ThemeInterface;
use Tuxxedo\View\Lumi\Library\Attribute\LumiFunction;
use Tuxxedo\View\Lumi\Library\Directive\DefaultDirectives;
use Tuxxedo\View\Lumi\Library\Directive\MutableDirectives;
use Tuxxedo\View\Lumi\Library\Function\CustomFunction;
use Tuxxedo\View\Lumi\Library\Function\FunctionInterface;
use Tuxxedo\View\Lumi\Library\Function\FunctionProviderInterface;
use Tuxxedo\View\Lumi\Library\Function\PhpFunction;
use Tuxxedo\View\Lumi\Library\LibraryDiscoveryInterface;
use Tuxxedo\View\Lumi\Library\LibraryProviderInterface;
use Tuxxedo\View\Lumi\Library\Standard\StandardLibrary;
use Tuxxedo\View\Lumi\Optimizer\OptimizerInterface;
use Tuxxedo\View\Lumi\Optimizer\OptimizerPipeline;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableDiscoverer;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableKind;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableMetadata;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableMetadataInterface;
use Tuxxedo\View\Lumi\Runtime\Introspector\RuntimeIntrospector;
use Tuxxedo\View\Lumi\Runtime\Loader;
use Tuxxedo\View\Lumi\Runtime\LoaderInterface;
use Tuxxedo\View\Lumi\Runtime\Runtime;
use Tuxxedo\View\Lumi\Runtime\RuntimeFunctionPolicy;

class LumiConfigurator implements LumiConfiguratorInterface
{
    public private(set) string $viewDirectory = '';
    public private(set) string $viewExtension = '';
    public private(set) bool $viewAlwaysCompile = false;
    public private(set) bool $viewDisableErrorReporting = true;
    public private(set) string $viewCacheDirectory = '';

    public private(set) array $optimizers = [];

    public private(set) ?LoaderInterface $loader = null;

    public private(set) array $directives = [];
    public private(set) array $defaultDirectives = [];

    public private(set) array $functions = [];

    public private(set) RuntimeFunctionPolicy $functionPolicy = RuntimeFunctionPolicy::CUSTOM_ONLY;
    public private(set) array $functionProviders = [];

    public private(set) array $instanceCallClasses = [];

    /**
     * @var list<class-string>
     */
    public private(set) array $filterClasses = [];

    /**
     * @var array<string, ThemeInterface>
     */
    public private(set) array $highlightThemes = [];

    public private(set) bool $withStandardLibrary = true;

    final public function __construct(
        private readonly ContainerInterface $container,
    ) {
        $optimizers = [];

        foreach (LumiEngine::createDefaultOptimizers() as $optimizer) {
            $optimizers[$optimizer::class] = $optimizer;
        }

        $this->optimizers = $optimizers;
        $this->defaultDirectives = DefaultDirectives::defaults();
    }

    public static function fromConfig(
        ContainerInterface $container,
    ): static {
        $configurator = new static($container);
        $config = $container->resolve(LumiConfigInterface::class);

        if ($config->directory !== '') {
            $configurator->viewDirectory(
                directory: $config->directory,
            );
        }

        if ($config->cacheDirectory !== '') {
            $configurator->cacheDirectory(
                directory: $config->cacheDirectory,
            );
        }

        if ($config->extension !== '') {
            $configurator->viewExtension(
                extension: $config->extension,
            );
        }

        if ($config->alwaysCompile) {
            $configurator->enableAlwaysCompile();
        } else {
            $configurator->disableAlwaysCompile();
        }

        if ($config->disableErrorReporting) {
            $configurator->disableErrorReporting();
        } else {
            $configurator->enableErrorReporting();
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
        $this->defaultDirectives['lumi.strip_comments'] = true;

        return $this;
    }

    public function disableStripComments(): self
    {
        $this->defaultDirectives['lumi.strip_comments'] = false;

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
        return $this->defineFunction(
            handler: new PhpFunction(
                name: $name,
            ),
        );
    }

    public function allowAllFunctions(): self
    {
        $this->functionPolicy = RuntimeFunctionPolicy::ALLOW_ALL;

        return $this;
    }

    public function disallowAllFunctions(): self
    {
        $this->functions = [];
        $this->functionProviders = [];
        $this->functionPolicy = RuntimeFunctionPolicy::DISALLOW_ALL;

        return $this;
    }

    public function defineFunction(
        FunctionInterface $handler,
    ): self {
        $this->functions[$handler->name] = $handler;

        foreach ($handler->aliases as $alias) {
            $this->functions[$alias] = $handler;
        }

        if ($this->functionPolicy === RuntimeFunctionPolicy::DISALLOW_ALL) {
            $this->functionPolicy = RuntimeFunctionPolicy::CUSTOM_ONLY;
        }

        return $this;
    }

    /**
     * @param class-string $className
     */
    public function withFilterClass(
        string $className,
    ): self {
        $this->filterClasses[] = $className;

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

    public function withLibrary(
        LibraryProviderInterface|LibraryDiscoveryInterface $library,
    ): LumiConfiguratorInterface {
        if ($library instanceof LibraryDiscoveryInterface) {
            $this->loadDiscoveredLibrary($library);

            return $this;
        }

        if (($functionProvider = $library->functions()) !== null) {
            $this->withFunctionProvider($functionProvider);
        }

        return $this;
    }

    public function withoutStandardLibrary(): self
    {
        $this->withStandardLibrary = false;

        return $this;
    }

    public function allowAllInstanceCalls(): self
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

    public function withoutOptimizers(): self
    {
        $this->optimizers = [];

        return $this;
    }

    public function withCustomOptimizer(
        OptimizerInterface ...$optimizers,
    ): self {
        foreach ($optimizers as $optimizer) {
            $this->optimizers[$optimizer::class] = $optimizer;
        }

        return $this;
    }

    public function withHighlightTheme(
        ThemeInterface ...$themes,
    ): self {
        foreach ($themes as $theme) {
            $this->highlightThemes[$theme->identifier] = $theme;
        }

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

        return true;
    }

    private function loadDiscoveredLibrary(
        LibraryDiscoveryInterface $library,
    ): void {
        $instance = new \ReflectionObject($library);

        foreach ($instance->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $method = new MethodReflector(
                reflector: $method,
            );

            $callback = [
                $library,
                $method->name,
            ];

            if (!\is_callable($callback)) {
                continue; // @codeCoverageIgnore
            }

            if ($method->hasAttribute(LumiFunction::class)) {
                $function = $method->getAttribute(LumiFunction::class);

                $this->defineFunction(
                    handler: new CustomFunction(
                        name: $function->name,
                        implementation: fn (): mixed => $callback(...),
                        aliases: $function->aliases,
                    ),
                );
            }
        }
    }

    /**
     * @return array<string, FunctionInterface>
     */
    private function loadFunctionProvider(
        FunctionProviderInterface $provider,
    ): array {
        $functions = [];

        foreach ($provider->export($this->container) as $handler) {
            $functions[\strtolower($handler->name)] = $handler;

            foreach ($handler->aliases as $alias) {
                $functions[\strtolower($alias)] = $handler;
            }
        }

        return $functions;
    }

    /**
     * @return array<string, FunctionInterface>
     */
    private function buildCustomFunctions(): array
    {
        $customFunctions = [];

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

    public function build(): LumiViewRenderInterface
    {
        if ($this->withStandardLibrary) {
            $this->withLibrary(
                library: new StandardLibrary(),
            );

            foreach (StandardLibrary::filters() as $className) {
                $this->withFilterClass($className);
            }
        }

        $highlighter = new Highlighter(
            themeFactory: ThemeFactory::createDefault(
                themes: \array_values($this->highlightThemes),
            ),
        );
        $introspector = new RuntimeIntrospector(
            functions: $this->collectFunctionMetadata(),
            filters: $this->collectFilterMetadata(),
        );
        $compiler = null;

        if ($this->defaultDirectives !== DefaultDirectives::defaults()) {
            $compiler = Compiler::createWithDefaultProviders(
                state: new CompilerState(
                    directives: new MutableDirectives(
                        directives: $this->defaultDirectives,
                    ),
                ),
                optimizerPipeline: new OptimizerPipeline(
                    optimizers: $this->optimizers,
                ),
                highlighter: $highlighter,
                introspector: $introspector,
            );
        }

        return new LumiViewRender(
            loader: $this->loader ?? new Loader(
                directory: $this->viewDirectory,
                cacheDirectory: $this->viewCacheDirectory,
                extension: $this->viewExtension,
            ),
            runtime: new Runtime(
                engine: LumiEngine::createCustom(
                    compiler: $compiler,
                    highlighter: $highlighter,
                    optimizers: $compiler === null
                        ? $this->optimizers
                        : null,
                    introspector: $compiler === null
                        ? $introspector
                        : null,
                ),
                directives: \array_merge(
                    $this->directives,
                    $this->defaultDirectives,
                ),
                functions: \array_merge(
                    $this->buildCustomFunctions(),
                    $this->functions,
                ),
                functionPolicy: $this->functionPolicy,
                instanceCallClasses: $this->instanceCallClasses,
                container: $this->container,
            ),
            alwaysCompile: $this->viewAlwaysCompile,
            disableErrorReporting: $this->viewDisableErrorReporting,
        );
    }

    /**
     * @return list<CallableMetadataInterface>
     */
    private function collectFunctionMetadata(): array
    {
        $seen = [];
        $metadata = [];

        foreach ($this->functions as $function) {
            if (isset($seen[\spl_object_id($function)])) {
                continue;
            }

            $seen[\spl_object_id($function)] = true;
            $metadata[] = new CallableMetadata(
                name: $function->name,
                kind: CallableKind::LEGACY_INTERFACE,
                className: $function::class,
                methodName: 'call',
                wantsContext: true,
                aliases: \array_values($function->aliases),
            );
        }

        foreach ($this->functionProviders as $provider) {
            foreach ($provider->export($this->container) as $function) {
                if (isset($seen[\spl_object_id($function)])) {
                    continue;
                }

                $seen[\spl_object_id($function)] = true;
                $metadata[] = new CallableMetadata(
                    name: $function->name,
                    kind: CallableKind::LEGACY_INTERFACE,
                    className: $function::class,
                    methodName: 'call',
                    wantsContext: true,
                    aliases: \array_values($function->aliases),
                );
            }
        }

        return $metadata;
    }

    /**
     * @return list<CallableMetadataInterface>
     */
    private function collectFilterMetadata(): array
    {
        $metadata = [];
        $discoverer = new CallableDiscoverer();

        foreach ($this->filterClasses as $className) {
            foreach ($discoverer->discoverFilters($className) as $entry) {
                $metadata[] = $entry;
            }
        }

        return $metadata;
    }
}
