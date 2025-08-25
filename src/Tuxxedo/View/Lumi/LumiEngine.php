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
use Tuxxedo\View\Lumi\Parser\Parser;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Parser\ParserInterface;
use Tuxxedo\View\ViewException;

class LumiEngine
{
    final private function __construct(
        public readonly LexerInterface $lexer,
        public readonly ParserInterface $parser,
        public readonly CompilerInterface $compiler,
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
                viewName: $file,
            );
        }

        return new CompiledFile(
            sourceFile: $viewName,
            sourceCode: $this->compiler->compile(
                stream: $this->parser->parse(
                    stream: $this->lexer->tokenizeByFile(
                        sourceFile: $file,
                    ),
                ),
            ),
        );
    }
}
