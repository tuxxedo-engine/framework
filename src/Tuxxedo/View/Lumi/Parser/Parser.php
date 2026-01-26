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
use Tuxxedo\View\Lumi\Parser\Handler\BlockParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\BreakParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\CommentParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\ConditionParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\ContinueParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\DeclareParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\DoWhileParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\EchoParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\ForParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\ForeachParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\IncludeParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\LayoutParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\LumiParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\ParserHandlerInterface;
use Tuxxedo\View\Lumi\Parser\Handler\TextParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\VoidParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\WhileParserHandler;
use Tuxxedo\View\Lumi\Syntax\Token\ElseIfToken;
use Tuxxedo\View\Lumi\Syntax\Token\ElseToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndBlockToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndForEachToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndForToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndWhileToken;
use Tuxxedo\View\Lumi\Syntax\Token\TokenInterface;

class Parser implements ParserInterface
{
    /**
     * @var array<class-string<TokenInterface>, ParserHandlerInterface>
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
            $parserHandlers[$handler->tokenClassName] = $handler;
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
                tokenClassName: ElseIfToken::class,
            ),
            new VoidParserHandler(
                tokenClassName: ElseToken::class,
            ),
            new WhileParserHandler(),
            new DoWhileParserHandler(),
            new VoidParserHandler(
                tokenClassName: EndWhileToken::class,
            ),
            new ContinueParserHandler(),
            new BreakParserHandler(),
            new DeclareParserHandler(),
            new ForeachParserHandler(),
            new VoidParserHandler(
                tokenClassName: EndForEachToken::class,
            ),
            new ForParserHandler(),
            new VoidParserHandler(
                tokenClassName: EndForToken::class,
            ),
            new LayoutParserHandler(),
            new BlockParserHandler(),
            new VoidParserHandler(
                tokenClassName: EndBlockToken::class,
            ),
            new LumiParserHandler(),
            new IncludeParserHandler(),
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

            if (!\array_key_exists($token::class, $this->handlers)) {
                throw ParserException::fromUnexpectedToken(
                    tokenName: $token::name(),
                    line: $token->line,
                );
            }

            $this->state->pushState();

            $nodes = \array_merge(
                $nodes,
                $this->handlers[$token::class]->parse($this, $stream),
            );

            $this->state->popState();
        }

        return new NodeStream(
            nodes: $nodes,
        );
    }
}
