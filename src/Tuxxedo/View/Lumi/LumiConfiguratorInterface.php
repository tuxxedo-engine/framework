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
use Tuxxedo\View\Lumi\Lexer\LexerInterface;
use Tuxxedo\View\Lumi\Parser\ParserInterface;
use Tuxxedo\View\Lumi\Runtime\LumiDirectivesInterface;
use Tuxxedo\View\Lumi\Runtime\LumiLoaderInterface;
use Tuxxedo\View\Lumi\Runtime\LumiRuntimeFunctionMode;
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

    public ?LumiLoaderInterface $loader {
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
     * @var array<string, \Closure(string $function, array<mixed> $arguments, LumiDirectivesInterface $directives): mixed>
     */
    public array $customFunctions {
        get;
    }

    public LumiRuntimeFunctionMode $functionMode {
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

    public function enableAlwaysCompile(): self;
    public function disableAlwaysCompile(): self;

    public function cacheDirectory(
        string $directory,
    ): self;

    public function allowFunction(
        string $name,
    ): self;

    public function allowAllFunctions(): self;
    public function disallowAllFunctions(): self;

    /**
     * @param \Closure(string $function, array<mixed> $arguments, LumiDirectivesInterface $directives): mixed $handler
     */
    public function defineFunction(
        string $name,
        \Closure $handler,
    ): self;

    /**
     * @param \Closure(mixed $value, LumiDirectivesInterface $directives): mixed $handler
     */
    public function defineFilter(
        string $name,
        \Closure $handler,
    ): self;

    public function withoutDefaultFilters(): self;

    public function withoutDefaultFunctions(): self;

    public function useLexer(
        LexerInterface $lexer,
    ): self;

    public function useParser(
        ParserInterface $parser,
    ): self;

    public function useCompiler(
        CompilerInterface $compiler,
    ): self;

    public function useLoader(
        LumiLoaderInterface $loader,
    ): self;

    public function declare(
        string $name,
        string|int|float|bool|null $value,
    ): self;

    public function validate(): bool;

    public function build(): ViewRenderInterface;
}
