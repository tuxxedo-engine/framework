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
use Tuxxedo\View\Lumi\Compiler\Compiler;
use Tuxxedo\View\Lumi\Compiler\CompilerState;
use Tuxxedo\View\Lumi\Config\LumiConfigInterface;
use Tuxxedo\View\Lumi\Highlight\Highlighter;
use Tuxxedo\View\Lumi\Highlight\Theme\ThemeFactory;
use Tuxxedo\View\Lumi\Highlight\Theme\ThemeInterface;
use Tuxxedo\View\Lumi\Library\Directive\DefaultDirectives;
use Tuxxedo\View\Lumi\Library\Directive\MutableDirectives;
use Tuxxedo\View\Lumi\Library\Function\PhpFunction;
use Tuxxedo\View\Lumi\Library\Function\PhpFunctionInterface;
use Tuxxedo\View\Lumi\Library\Standard\StandardLibrary;
use Tuxxedo\View\Lumi\Optimizer\OptimizerInterface;
use Tuxxedo\View\Lumi\Optimizer\OptimizerPipeline;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableDiscoverer;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableMetadataInterface;
use Tuxxedo\View\Lumi\Runtime\Introspector\RuntimeIntrospector;
use Tuxxedo\View\Lumi\Runtime\Loader;
use Tuxxedo\View\Lumi\Runtime\LoaderInterface;
use Tuxxedo\View\Lumi\Runtime\Runtime;
use Tuxxedo\View\Lumi\Runtime\RuntimeContext;
use Tuxxedo\View\Lumi\Runtime\RuntimeFunctionPolicy;
use Tuxxedo\View\Lumi\Runtime\RuntimeInterface;

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

    /**
     * @var array<string, PhpFunctionInterface>
     */
    public private(set) array $phpFunctions = [];

    public private(set) RuntimeFunctionPolicy $functionPolicy = RuntimeFunctionPolicy::CUSTOM_ONLY;

    /**
     * @var list<class-string>
     */
    public private(set) array $functionClasses = [];

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

    /**
     * @param string[] $aliases
     * @param callable-string|null $mappedName
     */
    public function addFunction(
        string $name,
        ?string $mappedName = null,
        array $aliases = [],
    ): self {
        $handler = new PhpFunction(
            name: $name,
            aliases: $aliases,
            mappedName: $mappedName,
        );

        $this->phpFunctions[\strtolower($name)] = $handler;

        foreach ($aliases as $alias) {
            $this->phpFunctions[\strtolower($alias)] = $handler;
        }

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
        $this->phpFunctions = [];
        $this->functionPolicy = RuntimeFunctionPolicy::DISALLOW_ALL;

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

    /**
     * @param class-string $className
     */
    public function withFunctionClass(
        string $className,
    ): self {
        $this->functionClasses[] = $className;

        if ($this->functionPolicy === RuntimeFunctionPolicy::DISALLOW_ALL) {
            $this->functionPolicy = RuntimeFunctionPolicy::CUSTOM_ONLY;
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

    public function build(): LumiViewRenderInterface
    {
        if ($this->withStandardLibrary) {
            foreach (StandardLibrary::filters() as $className) {
                $this->withFilterClass($className);
            }

            foreach (StandardLibrary::functions() as $className) {
                $this->withFunctionClass($className);
            }

            StandardLibrary::registerPhpFunctions($this);
        }

        $compiler = null;
        $highlighter = new Highlighter(
            themeFactory: ThemeFactory::createDefault(
                themes: \array_values($this->highlightThemes),
            ),
        );

        $functionMetadata = $this->collectFunctionMetadata();
        $filterMetadata = $this->collectFilterMetadata();
        $introspector = new RuntimeIntrospector(
            functions: $functionMetadata,
            filters: $filterMetadata,
        );

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

        $container = $this->container;
        $instanceResolver = static function (string $className) use ($container): object {
            /** @var class-string $className */
            return $container->resolve($className);
        };

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
                instanceResolver: $instanceResolver,
                directives: \array_merge(
                    $this->directives,
                    $this->defaultDirectives,
                ),
                phpFunctions: $this->phpFunctions,
                functionPolicy: $this->functionPolicy,
                instanceCallClasses: $this->instanceCallClasses,
                filterDispatchers: self::buildDispatcherTable(
                    metadata: $filterMetadata,
                    instanceResolver: $instanceResolver,
                ),
                functionDispatchers: self::buildDispatcherTable(
                    metadata: $functionMetadata,
                    instanceResolver: $instanceResolver,
                ),
            ),
            alwaysCompile: $this->viewAlwaysCompile,
            disableErrorReporting: $this->viewDisableErrorReporting,
        );
    }

    /**
     * @param list<CallableMetadataInterface> $metadata
     * @param \Closure(class-string): object $instanceResolver
     *
     * @return array<string, \Closure(mixed[], RuntimeInterface): mixed>
     */
    private static function buildDispatcherTable(
        array $metadata,
        \Closure $instanceResolver,
    ): array {
        $table = [];

        foreach ($metadata as $entry) {
            $dispatcher = self::makeDispatcher(
                className: $entry->className,
                methodName: $entry->methodName,
                contextIndex: $entry->contextParameterIndex,
                instanceResolver: $instanceResolver,
            );

            $table[\strtolower($entry->name)] = $dispatcher;

            foreach ($entry->aliases as $alias) {
                $table[\strtolower($alias)] = $dispatcher;
            }
        }

        return $table;
    }

    /**
     * @param class-string $className
     * @param \Closure(class-string): object $instanceResolver
     *
     * @return \Closure(mixed[], RuntimeInterface): mixed
     */
    private static function makeDispatcher(
        string $className,
        string $methodName,
        ?int $contextIndex,
        \Closure $instanceResolver,
    ): \Closure {
        $handler = null;

        return static function (array $arguments, RuntimeInterface $runtime) use (&$handler, $instanceResolver, $className, $methodName, $contextIndex): mixed {
            $handler ??= $instanceResolver($className);

            if ($contextIndex !== null) {
                $arguments = [
                    ...\array_slice($arguments, 0, $contextIndex),
                    new RuntimeContext(
                        runtime: $runtime,
                    ),
                    ...\array_slice($arguments, $contextIndex),
                ];
            }

            /** @var callable $callable */
            $callable = [
                $handler,
                $methodName,
            ];

            return \call_user_func_array($callable, $arguments);
        };
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

    /**
     * @return list<CallableMetadataInterface>
     */
    private function collectFunctionMetadata(): array
    {
        $metadata = [];
        $discoverer = new CallableDiscoverer();

        foreach ($this->functionClasses as $className) {
            foreach ($discoverer->discoverFunctions($className) as $entry) {
                $metadata[] = $entry;
            }
        }

        return $metadata;
    }
}
