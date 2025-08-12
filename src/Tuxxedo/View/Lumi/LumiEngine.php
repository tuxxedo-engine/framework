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

use Tuxxedo\View\Lumi\Lexer\Lexer;
use Tuxxedo\View\Lumi\Lexer\LexerInterface;

class LumiEngine
{
    final private function __construct(
        public LexerInterface $lexer, // @todo Fix visibility
        // @todo Parser
        // @todo Compiler
    ) {
    }

    public static function createDefault(): static
    {
        return new static(
            lexer: Lexer::createDefault(),
        );
    }

    public static function createCustom(
        LexerInterface $lexer,
        // @todo Parser
        // @todo Compiler
    ): static {
        return new static(
            lexer: $lexer,
        );
    }

    public function compileFile(
        string $file,
    ): void {
        // @todo Implement
        // @todo Return intermediate object for saving compiled version
    }

    public function compileDirectory(
        string $directory,
    ): void {
        // @todo Implement
        // @todo Return intermediate object for saving compiled version (batch)
    }
}
