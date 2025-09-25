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

use Tuxxedo\View\Lumi\Compiler\CompiledFile;
use Tuxxedo\View\Lumi\Compiler\CompiledFileInterface;
use Tuxxedo\View\Lumi\Compiler\Compiler;
use Tuxxedo\View\Lumi\Compiler\CompilerException;
use Tuxxedo\View\Lumi\Compiler\CompilerInterface;
use Tuxxedo\View\Lumi\Lexer\Lexer;
use Tuxxedo\View\Lumi\Lexer\LexerException;
use Tuxxedo\View\Lumi\Lexer\LexerInterface;
use Tuxxedo\View\Lumi\Optimizer\Dce\DceOptimizer;
use Tuxxedo\View\Lumi\Optimizer\OptimizerInterface;
use Tuxxedo\View\Lumi\Optimizer\Sccp\SccpOptimizer;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;
use Tuxxedo\View\Lumi\Parser\Parser;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Parser\ParserInterface;
use Tuxxedo\View\Lumi\Syntax\Highlight\Highlighter;
use Tuxxedo\View\Lumi\Syntax\Highlight\HighlighterInterface;
use Tuxxedo\View\Lumi\Syntax\Highlight\Theme\ThemeInterface;
use Tuxxedo\View\ViewException;

class LumiEngine
{
    /**
     * @param OptimizerInterface[] $optimizers
     */
    final private function __construct(
        public readonly LexerInterface $lexer,
        public readonly ParserInterface $parser,
        public readonly CompilerInterface $compiler,
        public readonly HighlighterInterface $highlighter,
        public readonly array $optimizers = [],
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
        return new static(
            lexer: self::createDefaultLexer(),
            parser: self::createDefaultParser(),
            compiler: self::createDefaultCompiler(),
            optimizers: self::createDefaultOptimizers(),
            highlighter: self::createDefaultHighlighter(),
        );
    }

    /**
     * @param OptimizerInterface[]|null $optimizers
     */
    public static function createCustom(
        ?LexerInterface $lexer = null,
        ?ParserInterface $parser = null,
        ?CompilerInterface $compiler = null,
        ?HighlighterInterface $highlighter = null,
        ?array $optimizers = null,
    ): static {
        return new static(
            lexer: $lexer ?? self::createDefaultLexer(),
            parser: $parser ?? self::createDefaultParser(),
            compiler: $compiler ?? self::createDefaultCompiler(),
            optimizers: $optimizers ?? self::createDefaultOptimizers(),
            highlighter: $highlighter ?? self::createDefaultHighlighter(),
        );
    }

    /**
     * @throws LexerException
     * @throws ParserException
     * @throws CompilerException
     * @throws ViewException
     */
    public function compileFile(
        string $file,
    ): CompiledFileInterface {
        $viewName = \strstr($file, '.lumi', true);

        if ($viewName === false) {
            throw ViewException::fromUnableToDetermineViewName(
                view: $file,
            );
        }

        $nodes = $this->parseByFile($file);

        if (\sizeof($this->optimizers) > 0) {
            foreach ($this->optimizers as $optimizer) {
                $nodes = $optimizer->optimize($nodes);
            }
        }

        return new CompiledFile(
            sourceFile: $viewName,
            sourceCode: $this->compiler->compile(
                stream: $nodes,
            ),
        );
    }

    /**
     * @throws LexerException
     * @throws ParserException
     * @throws CompilerException
     * @throws ViewException
     */
    public function compileString(
        string $source,
    ): string {
        $nodes = $this->parseByString($source);

        if (\sizeof($this->optimizers) > 0) {
            foreach ($this->optimizers as $optimizer) {
                $nodes = $optimizer->optimize($nodes);
            }
        }

        return $this->compiler->compile(
            stream: $nodes,
        );
    }

    private function parseByFile(
        string $file,
    ): NodeStreamInterface {
        return $this->parser->parse(
            stream: $this->lexer->tokenizeByFile(
                sourceFile: $file,
            ),
        );
    }

    private function parseByString(
        string $source,
    ): NodeStreamInterface {
        return $this->parser->parse(
            stream: $this->lexer->tokenizeByString(
                sourceCode: $source,
            ),
        );
    }

    /**
     * @throws LexerException
     * @throws ParserException
     */
    public function highlightFile(
        string $file,
        ThemeInterface $theme,
        bool $optimized = true,
    ): string {
        $nodes = $this->parseByFile($file);

        if ($optimized && \sizeof($this->optimizers) > 0) {
            foreach ($this->optimizers as $optimizer) {
                $nodes = $optimizer->optimize($nodes);
            }
        }

        return $this->highlighter->highlight(
            theme: $theme,
            stream: $nodes,
        );
    }

    /**
     * @throws LexerException
     * @throws ParserException
     */
    public function highlightString(
        string $source,
        ThemeInterface $theme,
        bool $optimized = true,
    ): string {
        $nodes = $this->parseByString($source);

        if ($optimized && \sizeof($this->optimizers) > 0) {
            foreach ($this->optimizers as $optimizer) {
                $nodes = $optimizer->optimize($nodes);
            }
        }

        return $this->highlighter->highlight(
            theme: $theme,
            stream: $nodes,
        );
    }
}
