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

use Tuxxedo\View\Lumi\Compiler\CompilerInterface;
use Tuxxedo\View\Lumi\Highlight\HighlighterInterface;
use Tuxxedo\View\Lumi\Lexer\LexerInterface;
use Tuxxedo\View\Lumi\Library\Filter\FilterInterface;
use Tuxxedo\View\Lumi\Library\Filter\FilterProviderInterface;
use Tuxxedo\View\Lumi\Library\Function\FunctionInterface;
use Tuxxedo\View\Lumi\Library\Function\FunctionProviderInterface;
use Tuxxedo\View\Lumi\Library\LibraryInterface;
use Tuxxedo\View\Lumi\Optimizer\OptimizerInterface;
use Tuxxedo\View\Lumi\Parser\ParserInterface;
use Tuxxedo\View\Lumi\Runtime\LoaderInterface;
use Tuxxedo\View\Lumi\Runtime\RuntimeFunctionPolicy;
use Tuxxedo\View\ViewRenderInterface;

interface LumiConfiguratorInterface
{
    public string $viewDirectory {
        get;
    }

    public string $viewExtension {
        get;
    }

    public bool $viewAlwaysCompile {
        get;
    }

    public bool $viewDisableErrorReporting {
        get;
    }

    public string $viewCacheDirectory {
        get;
    }

    public ?LexerInterface $lexer {
        get;
    }

    public ?ParserInterface $parser {
        get;
    }

    public ?CompilerInterface $compiler {
        get;
    }

    public ?HighlighterInterface $highlighter {
        get;
    }

    /**
     * @var array<class-string<OptimizerInterface>, OptimizerInterface>
     */
    public array $optimizers {
        get;
    }

    public ?LoaderInterface $loader {
        get;
    }

    /**
     * @var array<string, string|int|float|bool|null>
     */
    public array $directives {
        get;
    }

    /**
     * @var array<string, string|int|float|bool|null>
     */
    public array $defaultDirectives {
        get;
    }

    /**
     * @var string[]
     */
    public array $functions {
        get;
    }

    /**
     * @var array<string, FunctionInterface>
     */
    public array $customFunctions {
        get;
    }

    public RuntimeFunctionPolicy $functionPolicy {
        get;
    }

    /**
     * @var FunctionProviderInterface[]
     */
    public array $functionProviders {
        get;
    }

    /**
     * @var array<class-string>
     */
    public array $instanceCallClasses {
        get;
    }

    /**
     * @var array<string, FilterInterface>
     */
    public array $customFilters {
        get;
    }

    /**
     * @var FilterProviderInterface[]
     */
    public array $filterProviders {
        get;
    }

    public bool $withStandardLibrary {
        get;
    }

    public function viewDirectory(
        string $directory,
    ): self;

    public function viewExtension(
        string $extension,
    ): self;

    public function enableAutoescape(): self;
    public function disableAutoescape(): self;

    public function enableStripComments(): self;
    public function disableStripComments(): self;

    public function enableAlwaysCompile(): self;
    public function disableAlwaysCompile(): self;

    public function enableErrorReporting(): self;
    public function disableErrorReporting(): self;

    public function cacheDirectory(
        string $directory,
    ): self;

    public function allowFunction(
        string $name,
    ): self;

    public function allowAllFunctions(): self;
    public function disallowAllFunctions(): self;

    public function defineFunction(
        FunctionInterface $handler,
    ): self;

    public function defineFilter(
        FilterInterface $handler,
    ): self;

    public function withFilterProvider(
        FilterProviderInterface $provider,
    ): self;

    public function withFunctionProvider(
        FunctionProviderInterface $provider,
    ): self;

    public function withLibrary(
        LibraryInterface $library,
    ): self;

    public function withStandardLibrary(): self;

    public function withoutStandardLibrary(): self;

    public function withAnyInstanceCall(): self;

    /**
     * @param class-string $className
     */
    public function withAllowedInstanceCall(
        string ...$className,
    ): self;

    public function withDefaultLexer(): self;

    public function useLexer(
        LexerInterface $lexer,
    ): self;

    public function withDefaultParser(): self;

    public function useParser(
        ParserInterface $parser,
    ): self;

    public function withDefaultCompiler(): self;

    public function useCompiler(
        CompilerInterface $compiler,
    ): self;

    public function withoutOptimizers(): self;

    public function withSccpOptimizer(): self;
    public function withoutSccpOptimizer(): self;

    public function withDceOptimizer(): self;
    public function withoutDceOptimizer(): self;

    public function withCustomOptimizer(
        OptimizerInterface $optimizer,
    ): self;

    public function withDefaultHighlighter(): self;

    public function useHighlighter(
        HighlighterInterface $highlighter,
    ): self;

    public function withDefaultLoader(): self;

    public function useLoader(
        LoaderInterface $loader,
    ): self;

    public function declare(
        string $name,
        string|int|float|bool|null $value,
    ): self;

    public function validate(): bool;

    public function build(): ViewRenderInterface;
}
