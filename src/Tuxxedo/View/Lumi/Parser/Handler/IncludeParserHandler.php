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

namespace Tuxxedo\View\Lumi\Parser\Handler;

use Tuxxedo\View\Lumi\Lexer\TokenStream;
use Tuxxedo\View\Lumi\Lexer\TokenStreamInterface;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Parser\ParserInterface;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayNode;
use Tuxxedo\View\Lumi\Syntax\Node\FunctionCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\IncludeNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Token\EndToken;
use Tuxxedo\View\Lumi\Syntax\Token\IncludeToken;

class IncludeParserHandler implements ParserHandlerInterface
{
    public private(set) string $tokenClassName = IncludeToken::class;

    /**
     * @return NodeInterface[]
     */
    public function parse(
        ParserInterface $parser,
        TokenStreamInterface $stream,
    ): array {
        $include = $stream->consume();

        if (!$include instanceof IncludeToken) {
            throw ParserException::fromUnexpectedToken(
                tokenName: $include::name(),
                line: $include->line,
            );
        }

        $tokens = [];

        while (!$stream->eof()) {
            $token = $stream->current();

            if ($token instanceof EndToken) {
                $stream->consume();

                break;
            }

            $tokens[] = $token;

            $stream->consume();
        }

        $expr = $parser->expressionParser->parse(
            stream: new TokenStream(
                tokens: $tokens,
            ),
            startingLine: $include->line,
        );

        if (
            !$expr instanceof FunctionCallNode ||
            (
                !$expr->name instanceof IdentifierNode ||
                \mb_strtolower($expr->name->name) !== 'include'
            )
        ) {
            throw ParserException::fromInvalidIncludeSyntax(
                line: $include->line,
            );
        }

        $argc = \sizeof($expr->arguments);

        if ($argc < 1 || $argc > 2) {
            throw ParserException::fromIncludeArgumentCount(
                argc: $argc,
                line: $include->line,
            );
        }

        if ($include->op1 === 'braceless' && $argc > 1) {
            throw ParserException::fromIncludeBracesRequired(
                line: $include->line,
            );
        }

        $scope = $expr->arguments[1] ?? null;

        if ($scope !== null && !$scope instanceof ArrayNode) {
            throw ParserException::fromIncludeScopeMustBeAnArray(
                line: $include->line,
            );
        }

        return [
            new IncludeNode(
                file: $expr->arguments[0],
                scope: $scope,
            ),
        ];
    }
}
