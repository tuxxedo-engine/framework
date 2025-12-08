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

namespace Tuxxedo\View\Lumi\Lexer\Handler;

use Tuxxedo\View\Lumi\Lexer\Expression\ExpressionLexerInterface;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\AlwaysExpressiveInterface;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\AlwaysStandaloneInterface;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\BlockBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\BlockHandlerInterface;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\BlockHandlerState;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\BreakBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\ContinueBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\DeclareBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\DoBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\ElseBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\ElseIfBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\EndBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\EndForBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\EndForEachBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\EndIfBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\EndWhileBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\ForBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\ForEachBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\IfBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\IncludeBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\LayoutBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\SetBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\WhileBlockHandler;
use Tuxxedo\View\Lumi\Lexer\LexerException;
use Tuxxedo\View\Lumi\Lexer\LexerStateInterface;

class BlockTokenHandler implements TokenHandlerInterface
{
    /**
     * @var array<string, BlockHandlerInterface>
     */
    private readonly array $handlers;

    /**
     * @param BlockHandlerInterface[] $handlers
     */
    final private function __construct(
        array $handlers,
    ) {
        $blockHandlers = [];

        foreach ($handlers as $handler) {
            $blockHandlers[$handler->directive] = $handler;
        }

        $this->handlers = $blockHandlers;
    }

    /**
     * @return BlockHandlerInterface[]
     */
    public static function getDefaultHandlers(): array
    {
        return [
            new BlockBlockHandler(),
            new BreakBlockHandler(),
            new ContinueBlockHandler(),
            new DeclareBlockHandler(),
            new DoBlockHandler(),
            new ElseBlockHandler(),
            new ElseIfBlockHandler(),
            new EndBlockHandler(),
            new EndForBlockHandler(),
            new EndForEachBlockHandler(),
            new EndIfBlockHandler(),
            new EndWhileBlockHandler(),
            new ForBlockHandler(),
            new ForEachBlockHandler(),
            new IfBlockHandler(),
            new IncludeBlockHandler(),
            new LayoutBlockHandler(),
            new SetBlockHandler(),
            new WhileBlockHandler(),
        ];
    }

    /**
     * @param BlockHandlerInterface[] $handlers
     */
    public static function createWithDefaultHandlers(
        array $handlers = [],
    ): static {
        return new static(
            handlers: \array_merge(
                self::getDefaultHandlers(),
                $handlers,
            ),
        );
    }

    /**
     * @param BlockHandlerInterface[] $handlers
     */
    public static function createWithoutDefaultHandlers(
        array $handlers = [],
    ): static {
        return new static(
            handlers: $handlers,
        );
    }

    public function getStartingSequence(): string
    {
        return '{%';
    }

    public function getEndingSequence(): string
    {
        return '%}';
    }

    public function tokenize(
        int $startingLine,
        string $buffer,
        ExpressionLexerInterface $expressionLexer,
        LexerStateInterface $state,
    ): array {
        $buffer = \mb_trim($buffer);

        if (\mb_strpos($buffer, ' ') !== false) {
            [$directive, $expr] = \explode(' ', $buffer, 2);
            $directive = \mb_strtolower($directive);
        } else {
            $directive = \mb_strtolower($buffer);
        }

        if (
            !\array_key_exists($directive, $this->handlers) ||
            (
                isset($expr) &&
                $this->handlers[$directive] instanceof AlwaysStandaloneInterface
            ) ||
            (
                !isset($expr) &&
                $this->handlers[$directive] instanceof AlwaysExpressiveInterface
            )
        ) {
            throw LexerException::fromUnexpectedSequenceFound(
                sequence: $directive,
                line: $startingLine,
            );
        }

        return $this->handlers[$directive]->lex(
            startingLine: $startingLine,
            expression: $expr ?? '',
            expressionLexer: $expressionLexer,
            state: $state,
            blockState: isset($expr)
                ? BlockHandlerState::EXPRESSIVE
                : BlockHandlerState::STANDALONE,
        );
    }
}
