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
use Tuxxedo\View\Lumi\Highlight\Highlighter;
use Tuxxedo\View\Lumi\Highlight\HighlighterInterface;
use Tuxxedo\View\Lumi\Highlight\HighlightException;
use Tuxxedo\View\Lumi\Highlight\Theme\ThemeInterface;
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
use Tuxxedo\View\ViewException;

interface LumiEngineInterface
{
    public LexerInterface $lexer {
        get;
    }

    public ParserInterface $parser {
        get;
    }

    public CompilerInterface $compiler {
        get;
    }

    public HighlighterInterface $highlighter {
        get;
    }

    /**
     * @var OptimizerInterface[]
     */
    public array $optimizers {
        get;
    }

    /**
     * @throws LexerException
     * @throws ParserException
     * @throws CompilerException
     * @throws ViewException
     */
    public function compileFile(
        string $file,
    ): CompiledFileInterface;

    /**
     * @throws LexerException
     * @throws ParserException
     * @throws CompilerException
     * @throws ViewException
     */
    public function compileString(
        string $source,
    ): string;

    public function parseByFile(
        string $file,
    ): NodeStreamInterface;

    public function parseByString(
        string $source,
    ): NodeStreamInterface;

    /**
     * @throws LexerException
     * @throws ParserException
     * @throws HighlightException
     */
    public function highlightFile(
        string $file,
        ThemeInterface|string $theme,
        bool $optimized = true,
    ): string;

    /**
     * @throws LexerException
     * @throws ParserException
     * @throws HighlightException
     */
    public function highlightString(
        string $source,
        ThemeInterface|string $theme,
        bool $optimized = true,
    ): string;
}
