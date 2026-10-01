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

use Tuxxedo\View\Lumi\Compiler\CompiledFile;
use Tuxxedo\View\Lumi\Compiler\CompiledFileInterface;
use Tuxxedo\View\Lumi\Compiler\Compiler;
use Tuxxedo\View\Lumi\Compiler\CompilerInterface;
use Tuxxedo\View\Lumi\Highlight\Highlighter;
use Tuxxedo\View\Lumi\Highlight\HighlighterInterface;
use Tuxxedo\View\Lumi\Highlight\Theme\ThemeInterface;
use Tuxxedo\View\Lumi\Lexer\Lexer;
use Tuxxedo\View\Lumi\Lexer\LexerInterface;
use Tuxxedo\View\Lumi\Optimizer\Dce\DceOptimizer;
use Tuxxedo\View\Lumi\Optimizer\OptimizerInterface;
use Tuxxedo\View\Lumi\Optimizer\OptimizerPipeline;
use Tuxxedo\View\Lumi\Optimizer\Sccp\SccpOptimizer;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;
use Tuxxedo\View\Lumi\Parser\Parser;
use Tuxxedo\View\Lumi\Parser\ParserInterface;
use Tuxxedo\View\ViewException;

readonly class LumiEngine implements LumiEngineInterface
{
    final private function __construct(
        public LexerInterface $lexer,
        public ParserInterface $parser,
        public CompilerInterface $compiler,
        public HighlighterInterface $highlighter,
    ) {
    }

    public static function createDefaultLexer(): LexerInterface
    {
        return Lexer::createWithDefaultHandlers();
    }

    public static function createDefaultParser(): ParserInterface
    {
        return Parser::createWithDefaultHandlers();
    }

    public static function createDefaultCompiler(): CompilerInterface
    {
        return Compiler::createWithDefaultProviders();
    }

    public static function createDefaultHighlighter(): HighlighterInterface
    {
        return new Highlighter();
    }

    /**
     * @return OptimizerInterface[]
     */
    public static function createDefaultOptimizers(): array
    {
        return [
            new SccpOptimizer(),
            new DceOptimizer(),
        ];
    }

    public static function createDefault(): static
    {
        $optimizers = self::createDefaultOptimizers();
        $highlighter = self::createDefaultHighlighter();

        return new static(
            lexer: self::createDefaultLexer(),
            parser: self::createDefaultParser(),
            compiler: Compiler::createWithDefaultProviders(
                optimizerPipeline: new OptimizerPipeline(
                    optimizers: $optimizers,
                ),
                highlighter: $highlighter,
            ),
            highlighter: $highlighter,
        );
    }

    /**
     * @param OptimizerInterface[]|null $optimizers
     *
     * @throws LumiException
     */
    public static function createCustom(
        ?LexerInterface $lexer = null,
        ?ParserInterface $parser = null,
        ?CompilerInterface $compiler = null,
        ?HighlighterInterface $highlighter = null,
        ?array $optimizers = null,
    ): static {
        if ($compiler !== null && $optimizers !== null) {
            throw LumiException::fromAmbiguousCompilerAndOptimizers();
        }

        $highlighter = $highlighter ?? self::createDefaultHighlighter();

        return new static(
            lexer: $lexer ?? self::createDefaultLexer(),
            parser: $parser ?? self::createDefaultParser(),
            compiler: $compiler ?? Compiler::createWithDefaultProviders(
                optimizerPipeline: new OptimizerPipeline(
                    optimizers: $optimizers ?? self::createDefaultOptimizers(),
                ),
                highlighter: $highlighter,
            ),
            highlighter: $highlighter,
        );
    }

    public function compileFile(
        string $file,
    ): CompiledFileInterface {
        $viewName = \strstr($file, '.lumi', true);

        if ($viewName === false) {
            throw ViewException::fromUnableToDetermineViewName(
                view: $file,
            );
        }

        return new CompiledFile(
            sourceFile: $viewName,
            sourceCode: $this->compiler->compile(
                stream: $this->parseByFile($file),
            ),
        );
    }

    public function compileString(
        string $source,
    ): string {
        return $this->compiler->compile(
            stream: $this->parseByString($source),
        );
    }

    public function parseByFile(
        string $file,
    ): NodeStreamInterface {
        return $this->parser->parse(
            stream: $this->lexer->tokenizeByFile(
                sourceFile: $file,
            ),
        );
    }

    public function parseByString(
        string $source,
    ): NodeStreamInterface {
        return $this->parser->parse(
            stream: $this->lexer->tokenizeByString(
                sourceCode: $source,
            ),
        );
    }

    public function highlightFile(
        string $file,
        ThemeInterface|string $theme,
    ): string {
        return $this->highlighter->highlight(
            theme: $theme,
            stream: $this->parseByFile($file),
        );
    }

    public function highlightString(
        string $source,
        ThemeInterface|string $theme,
    ): string {
        return $this->highlighter->highlightString(
            theme: $theme,
            source: $source,
        );
    }
}
