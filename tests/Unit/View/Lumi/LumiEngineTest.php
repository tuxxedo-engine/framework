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

namespace Unit\View\Lumi;

use Fixture\View\Lumi\RecordingOptimizer;
use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Compiler\CompiledFile;
use Tuxxedo\View\Lumi\Compiler\Compiler;
use Tuxxedo\View\Lumi\Highlight\Highlighter;
use Tuxxedo\View\Lumi\Highlight\Theme\LumiDark;
use Tuxxedo\View\Lumi\Lexer\Lexer;
use Tuxxedo\View\Lumi\LumiEngine;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;
use Tuxxedo\View\Lumi\Parser\Parser;
use Tuxxedo\View\ViewException;

class LumiEngineTest extends TestCase
{
    /**
     * @var string[]
     */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (\is_file($file)) {
                @\unlink($file);
            }
        }

        $this->tempFiles = [];
    }

    private function writeLumiFile(
        string $contents,
    ): string {
        $base = \str_replace('\\', '/', \sys_get_temp_dir());
        $file = $base . '/tuxxedo_lumi_engine_' . \uniqid('', true) . '.lumi';

        \file_put_contents($file, $contents);

        $this->tempFiles[] = $file;

        return $file;
    }

    public function testCreateDefaultExposesAllDefaultDependencies(): void
    {
        $engine = LumiEngine::createDefault();

        self::assertInstanceOf(Lexer::class, $engine->lexer);
        self::assertInstanceOf(Parser::class, $engine->parser);
        self::assertInstanceOf(Compiler::class, $engine->compiler);
        self::assertInstanceOf(Highlighter::class, $engine->highlighter);
        self::assertSame(
            \sizeof(LumiEngine::createDefaultOptimizers()),
            \sizeof($engine->optimizers),
        );
    }

    public function testCreateCustomAcceptsAllProvidedDependencies(): void
    {
        $lexer = LumiEngine::createDefaultLexer();
        $parser = LumiEngine::createDefaultParser();
        $compiler = LumiEngine::createDefaultCompiler();
        $highlighter = LumiEngine::createDefaultHighlighter();
        $optimizers = [
            new RecordingOptimizer(),
        ];

        $engine = LumiEngine::createCustom(
            lexer: $lexer,
            parser: $parser,
            compiler: $compiler,
            highlighter: $highlighter,
            optimizers: $optimizers,
        );

        self::assertSame($lexer, $engine->lexer);
        self::assertSame($parser, $engine->parser);
        self::assertSame($compiler, $engine->compiler);
        self::assertSame($highlighter, $engine->highlighter);
        self::assertSame($optimizers, $engine->optimizers);
    }

    public function testCreateCustomFallsBackToDefaultsForOmittedDependencies(): void
    {
        $engine = LumiEngine::createCustom();

        self::assertInstanceOf(Lexer::class, $engine->lexer);
        self::assertInstanceOf(Parser::class, $engine->parser);
        self::assertInstanceOf(Compiler::class, $engine->compiler);
        self::assertInstanceOf(Highlighter::class, $engine->highlighter);
        self::assertNotSame([], $engine->optimizers);
    }

    public function testCreateCustomAcceptsEmptyOptimizerList(): void
    {
        $engine = LumiEngine::createCustom(
            optimizers: [],
        );

        self::assertSame([], $engine->optimizers);
    }

    public function testCreateDefaultOptimizersReturnsTwoOptimizers(): void
    {
        $optimizers = LumiEngine::createDefaultOptimizers();

        self::assertCount(2, $optimizers);
    }

    public function testParseByStringReturnsNodeStream(): void
    {
        $engine = LumiEngine::createDefault();

        self::assertInstanceOf(
            NodeStreamInterface::class,
            $engine->parseByString('hello'),
        );
    }

    public function testParseByFileReturnsNodeStream(): void
    {
        $engine = LumiEngine::createDefault();
        $file = $this->writeLumiFile('hello');

        self::assertInstanceOf(
            NodeStreamInterface::class,
            $engine->parseByFile($file),
        );
    }

    public function testCompileStringEmitsPhpPrefixAndSourceContent(): void
    {
        $engine = LumiEngine::createDefault();

        $output = $engine->compileString('hello world');

        self::assertStringStartsWith('<?php declare(strict_types=1); ?>', $output);
        self::assertStringContainsString('hello world', $output);
    }

    public function testCompileFileReturnsCompiledFileWithSourceFileWithoutLumiExtension(): void
    {
        $engine = LumiEngine::createDefault();
        $file = $this->writeLumiFile('hello');

        $compiled = $engine->compileFile($file);

        self::assertInstanceOf(CompiledFile::class, $compiled);
        self::assertSame(\substr($file, 0, -5), $compiled->sourceFile);
        self::assertStringContainsString('hello', $compiled->sourceCode);
    }

    public function testCompileFileThrowsWhenFilenameIsMissingLumiExtension(): void
    {
        $engine = LumiEngine::createDefault();

        self::expectException(ViewException::class);

        $engine->compileFile('not-a-lumi-file.txt');
    }

    public function testCompileStringRunsOptimizersAtLeastOnce(): void
    {
        $optimizer = new RecordingOptimizer();

        $engine = LumiEngine::createCustom(
            optimizers: [
                $optimizer,
            ],
        );

        $engine->compileString('hello');

        self::assertSame(1, $optimizer->callCount);
    }

    public function testCompileStringLoopsOptimizerUntilNoChange(): void
    {
        $optimizer = new RecordingOptimizer(
            changeCount: 2,
        );

        $engine = LumiEngine::createCustom(
            optimizers: [
                $optimizer,
            ],
        );

        $engine->compileString('hello');

        self::assertSame(3, $optimizer->callCount);
    }

    public function testCompileFileLoopsOptimizerUntilNoChange(): void
    {
        $optimizer = new RecordingOptimizer(
            changeCount: 1,
        );

        $engine = LumiEngine::createCustom(
            optimizers: [
                $optimizer,
            ],
        );

        $file = $this->writeLumiFile('hello');

        $engine->compileFile($file);

        self::assertSame(2, $optimizer->callCount);
    }

    public function testCompileStringSkipsOptimizerLoopWhenNoOptimizersConfigured(): void
    {
        $engine = LumiEngine::createCustom(
            optimizers: [],
        );

        $output = $engine->compileString('hello');

        self::assertStringContainsString('hello', $output);
    }

    public function testHighlightStringReturnsHtmlOutput(): void
    {
        $engine = LumiEngine::createDefault();

        $output = $engine->highlightString(
            source: 'hello',
            theme: new LumiDark(),
        );

        self::assertStringContainsString('hello', $output);
        self::assertStringContainsString('<span', $output);
    }

    public function testHighlightFileReturnsHtmlOutput(): void
    {
        $engine = LumiEngine::createDefault();
        $file = $this->writeLumiFile('hello');

        $output = $engine->highlightFile(
            file: $file,
            theme: new LumiDark(),
        );

        self::assertStringContainsString('hello', $output);
        self::assertStringContainsString('<span', $output);
    }

    public function testHighlightStringResolvesThemeByIdentifier(): void
    {
        $engine = LumiEngine::createDefault();

        $output = $engine->highlightString(
            source: 'hello',
            theme: 'dark',
        );

        self::assertStringContainsString('<span', $output);
    }

    public function testHighlightStringRunsOptimizersWhenOptimizedFlagTrue(): void
    {
        $optimizer = new RecordingOptimizer();

        $engine = LumiEngine::createCustom(
            optimizers: [
                $optimizer,
            ],
        );

        $engine->highlightString(
            source: 'hello',
            theme: new LumiDark(),
        );

        self::assertSame(1, $optimizer->callCount);
    }

    public function testHighlightStringSkipsOptimizersWhenOptimizedFlagFalse(): void
    {
        $optimizer = new RecordingOptimizer();

        $engine = LumiEngine::createCustom(
            optimizers: [
                $optimizer,
            ],
        );

        $engine->highlightString(
            source: 'hello',
            theme: new LumiDark(),
            optimized: false,
        );

        self::assertSame(0, $optimizer->callCount);
    }

    public function testHighlightFileRunsOptimizersWhenOptimizedFlagTrue(): void
    {
        $optimizer = new RecordingOptimizer();

        $engine = LumiEngine::createCustom(
            optimizers: [
                $optimizer,
            ],
        );

        $file = $this->writeLumiFile('hello');

        $engine->highlightFile(
            file: $file,
            theme: new LumiDark(),
        );

        self::assertSame(1, $optimizer->callCount);
    }

    public function testHighlightFileSkipsOptimizersWhenOptimizedFlagFalse(): void
    {
        $optimizer = new RecordingOptimizer();

        $engine = LumiEngine::createCustom(
            optimizers: [
                $optimizer,
            ],
        );

        $file = $this->writeLumiFile('hello');

        $engine->highlightFile(
            file: $file,
            theme: new LumiDark(),
            optimized: false,
        );

        self::assertSame(0, $optimizer->callCount);
    }
}
