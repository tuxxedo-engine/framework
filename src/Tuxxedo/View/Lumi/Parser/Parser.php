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

namespace Tuxxedo\View\Lumi\Parser;

use Tuxxedo\View\Lumi\Lexer\TokenStreamInterface;
use Tuxxedo\View\Lumi\Parser\Expression\ExpressionParser;
use Tuxxedo\View\Lumi\Parser\Expression\ExpressionParserInterface;
use Tuxxedo\View\Lumi\Parser\Handler\AssignmentParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\BreakParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\CommentParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\ConditionParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\ContinueParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\DeclareParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\DoWhileParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\EchoParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\ForParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\ForeachParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\ParserHandlerInterface;
use Tuxxedo\View\Lumi\Parser\Handler\TextParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\VoidParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\WhileParserHandler;
use Tuxxedo\View\Lumi\Syntax\Token\BuiltinTokenNames;

class Parser implements ParserInterface
{
    /**
     * @var ParserHandlerInterface[]
     */
    public readonly array $handlers;

    /**
     * @param ParserHandlerInterface[] $handlers
     *
     * @throws ParserException
     */
    final private function __construct(
        array $handlers,
        public readonly ExpressionParserInterface $expressionParser,
        public readonly ParserStateInterface $state,
    ) {
        $parserHandlers = [];

        foreach ($handlers as $handler) {
            $parserHandlers[$handler->tokenName] = $handler;
        }

        $this->handlers = $parserHandlers;
    }

    /**
     * @return ParserHandlerInterface[]
     */
    public static function getDefaultHandlers(): array
    {
        return [
            new TextParserHandler(),
            new CommentParserHandler(),
            new EchoParserHandler(),
            new AssignmentParserHandler(),
            new ConditionParserHandler(),
            new VoidParserHandler(
                tokenName: BuiltinTokenNames::ELSEIF->name,
            ),
            new VoidParserHandler(
                tokenName: BuiltinTokenNames::ELSE->name,
            ),
            new WhileParserHandler(),
            new DoWhileParserHandler(),
            new VoidParserHandler(
                tokenName: BuiltinTokenNames::ENDWHILE->name,
            ),
            new ContinueParserHandler(),
            new BreakParserHandler(),
            new DeclareParserHandler(),
            new ForeachParserHandler(),
            new VoidParserHandler(
                tokenName: BuiltinTokenNames::ENDFOREACH->name,
            ),
            new ForParserHandler(),
            new VoidParserHandler(
                tokenName: BuiltinTokenNames::ENDFOR->name,
            ),
        ];
    }

    public static function getDefaultExpressionParser(): ExpressionParserInterface
    {
        return new ExpressionParser();
    }

    public static function getDefaultParserState(): ParserStateInterface
    {
        return new ParserState();
    }

    /**
     * @param ParserHandlerInterface[] $handlers
     */
    public static function createWithDefaultHandlers(
        array $handlers = [],
        ?ExpressionParserInterface $expressionParser = null,
        ?ParserStateInterface $state = null,
    ): static {
        return new static(
            handlers: \array_merge(
                self::getDefaultHandlers(),
                $handlers,
            ),
            expressionParser: $expressionParser ?? self::getDefaultExpressionParser(),
            state: $state ?? self::getDefaultParserState(),
        );
    }

    /**
     * @param ParserHandlerInterface[] $handlers
     */
    public static function createWithoutDefaultHandlers(
        array $handlers = [],
        ?ExpressionParserInterface $expressionParser = null,
        ?ParserStateInterface $state = null,
    ): static {
        return new static(
            handlers: $handlers,
            expressionParser: $expressionParser ?? self::getDefaultExpressionParser(),
            state: $state ?? self::getDefaultParserState(),
        );
    }

    public function parse(
        TokenStreamInterface $stream,
    ): NodeStreamInterface {
        $nodes = [];

        while (!$stream->eof()) {
            $token = $stream->current();

            if (!\array_key_exists($token->type, $this->handlers)) {
                throw ParserException::fromUnexpectedToken(
                    tokenName: $token->type,
                    line: $token->line,
                );
            }

            $this->state->pushState();

            $nodes = \array_merge(
                $nodes,
                $this->handlers[$token->type]->parse($this, $stream),
            );

            $this->state->popState();
        }

        return new NodeStream(
            nodes: $nodes,
        );
    }
}
