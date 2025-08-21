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

use Tuxxedo\Collection\FileCollection;
use Tuxxedo\View\Lumi\Compiler\CompiledFile;
use Tuxxedo\View\Lumi\Compiler\CompiledFileBatch;
use Tuxxedo\View\Lumi\Compiler\CompiledFileBatchInterface;
use Tuxxedo\View\Lumi\Compiler\CompiledFileInterface;
use Tuxxedo\View\Lumi\Compiler\Compiler;
use Tuxxedo\View\Lumi\Compiler\CompilerInterface;
use Tuxxedo\View\Lumi\Lexer\Lexer;
use Tuxxedo\View\Lumi\Lexer\LexerException;
use Tuxxedo\View\Lumi\Lexer\LexerInterface;
use Tuxxedo\View\Lumi\Parser\Parser;
use Tuxxedo\View\Lumi\Parser\ParserInterface;

class LumiEngine
{
    final private function __construct(
        private LexerInterface $lexer,
        private ParserInterface $parser,
        private CompilerInterface $compiler,
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
        return Compiler::createWithDefaultHandlers();
    }

    public static function createDefault(): static
    {
        return new static(
            lexer: self::createDefaultLexer(),
            parser: self::createDefaultParser(),
            compiler: self::createDefaultCompiler(),
        );
    }

    public static function createCustom(
        ?LexerInterface $lexer = null,
        ?ParserInterface $parser = null,
        ?CompilerInterface $compiler = null,
    ): static {
        return new static(
            lexer: $lexer ?? self::createDefaultLexer(),
            parser: $parser ?? self::createDefaultParser(),
            compiler: $compiler ?? self::createDefaultCompiler(),
        );
    }

    /**
     * @throws LexerException
     */
    public function compileFile(
        string $file,
    ): CompiledFileInterface {
        return new CompiledFile(
            name: !\is_bool($name = \strstr($file, '.lumi', true)) ? $name : '',
            source: $this->compiler->compile(
                stream: $this->parser->parse(
                    stream: $this->lexer->tokenizeByFile(
                        sourceFile: $file,
                    ),
                )
            ),
        );
    }

    /**
     * @throws LexerException
     */
    public function compileDirectory(
        string $directory,
    ): CompiledFileBatchInterface {
        $files = FileCollection::fromRecursiveFileType(
            directory: $directory,
            extension: '.lumi',
        );

        if (\sizeof($files) === 0) {
            return new CompiledFileBatch(
                compiledFiles: [],
            );
        }

        $compiledFiles = [];

        foreach ($files as $file) {
            $compiledFiles[] = $this->compileFile($file);
        }

        return new CompiledFileBatch(
            compiledFiles: $compiledFiles,
        );
    }
}
