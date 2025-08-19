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
        // @todo Compiler
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

    public static function createDefault(): static
    {
        return new static(
            lexer: self::createDefaultLexer(),
            parser: self::createDefaultParser(),
        );
    }

    public static function createCustom(
        LexerInterface $lexer,
        ParserInterface $parser,
        // @todo Compiler
    ): static {
        return new static(
            lexer: $lexer,
            parser: $parser,
        );
    }

    /**
     * @throws LexerException
     */
    public function compileFile(
        string $file,
    ): CompiledFileInterface {
        $stream = $this->lexer->tokenizeByFile($file);

        var_dump($stream->tokens);

        $nodes = $this->parser->parse($stream);

        var_dump($nodes);

        // @todo Hand $tokens over to $this->compiler

        return new CompiledFile(
            name: !\is_bool($name = \strstr($file, '.lumi', true)) ? $name : '',
            source: '', // @todo Implement
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
            $tokens = $this->lexer->tokenizeByFile($file);

            // @todo Hand $tokens over to $this->parser and then $this->compiler
        }

        return new CompiledFileBatch(
            compiledFiles: $compiledFiles,
        );
    }
}
