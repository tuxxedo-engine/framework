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

namespace Support\View\Lumi\Runtime;

use Tuxxedo\View\Lumi\Compiler\CompiledFileInterface;
use Tuxxedo\View\Lumi\Compiler\Compiler;
use Tuxxedo\View\Lumi\Compiler\CompilerInterface;
use Tuxxedo\View\Lumi\Highlight\Highlighter;
use Tuxxedo\View\Lumi\Highlight\HighlighterInterface;
use Tuxxedo\View\Lumi\Highlight\Theme\ThemeInterface;
use Tuxxedo\View\Lumi\Lexer\Lexer;
use Tuxxedo\View\Lumi\Lexer\LexerInterface;
use Tuxxedo\View\Lumi\LumiEngineInterface;
use Tuxxedo\View\Lumi\Optimizer\OptimizerInterface;
use Tuxxedo\View\Lumi\Parser\NodeStream;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;
use Tuxxedo\View\Lumi\Parser\Parser;
use Tuxxedo\View\Lumi\Parser\ParserInterface;

class StubLumiEngine implements LumiEngineInterface
{
    public ?string $lastHighlightedSource = null;
    public ThemeInterface|string|null $lastHighlightedTheme = null;
    public ?bool $lastHighlightedOptimized = null;

    public LexerInterface $lexer;
    public ParserInterface $parser;
    public CompilerInterface $compiler;
    public HighlighterInterface $highlighter;

    /**
     * @var OptimizerInterface[]
     */
    public array $optimizers = [];

    public function __construct(
        public string $highlightOutput = '<highlighted/>',
    ) {
        $this->lexer = Lexer::createWithDefaultHandlers();
        $this->parser = Parser::createWithDefaultHandlers();
        $this->compiler = Compiler::createWithDefaultProviders();
        $this->highlighter = new Highlighter();
    }

    public function compileFile(
        string $file,
    ): CompiledFileInterface {
        throw new \LogicException('StubLumiEngine::compileFile not used');
    }

    public function compileString(
        string $source,
    ): string {
        return '';
    }

    public function parseByFile(
        string $file,
    ): NodeStreamInterface {
        return new NodeStream(
            nodes: [],
        );
    }

    public function parseByString(
        string $source,
    ): NodeStreamInterface {
        return new NodeStream(
            nodes: [],
        );
    }

    public function highlightFile(
        string $file,
        ThemeInterface|string $theme,
        bool $optimized = true,
    ): string {
        return $this->highlightOutput;
    }

    public function highlightString(
        string $source,
        ThemeInterface|string $theme,
        bool $optimized = true,
    ): string {
        $this->lastHighlightedSource = $source;
        $this->lastHighlightedTheme = $theme;
        $this->lastHighlightedOptimized = $optimized;

        return $this->highlightOutput;
    }
}
