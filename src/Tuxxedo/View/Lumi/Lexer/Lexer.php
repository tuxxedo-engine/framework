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

namespace Tuxxedo\View\Lumi\Lexer;

use Tuxxedo\View\Lumi\Lexer\Expression\ExpressionLexer;
use Tuxxedo\View\Lumi\Lexer\Expression\ExpressionLexerInterface;
use Tuxxedo\View\Lumi\Lexer\Handler\BlockTokenHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\CommentTokenHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\EchoTokenHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\TokenHandlerInterface;
use Tuxxedo\View\Lumi\Syntax\Token\TextToken;
use Tuxxedo\View\Lumi\Syntax\Token\TokenInterface;

// @todo Implement raw and endraw
class Lexer implements LexerInterface
{
    /**
     * @var array<string, TokenHandlerInterface>
     */
    private readonly array $sequences;
    private readonly int $maxTokenLength;

    /**
     * @param TokenHandlerInterface[] $handlers
     *
     * @throws LexerException
     */
    final private function __construct(
        array $handlers,
        public readonly ExpressionLexerInterface $expressionLexer,
        public readonly LexerStateInterface $state,
    ) {
        $maxTokenLength = 0;
        $sequences = [];

        foreach ($handlers as $handler) {
            $sequence = $handler->getStartingSequence();

            if (isset($sequences[$sequence])) {
                throw LexerException::fromDuplicateSequence(
                    sequence: $sequence,
                );
            }

            $sequences[$sequence] = $handler;
            $maxTokenLength = \max($maxTokenLength, \mb_strlen($sequence));
        }

        $this->maxTokenLength = $maxTokenLength;
        $this->sequences = $sequences;
    }

    /**
     * @return TokenHandlerInterface[]
     */
    public static function getDefaultHandlers(): array
    {
        return [
            new EchoTokenHandler(),
            new CommentTokenHandler(),
            new BlockTokenHandler(),
        ];
    }

    public static function getDefaultExpressionLexer(): ExpressionLexerInterface
    {
        return new ExpressionLexer();
    }

    public static function getDefaultLexerState(): LexerStateInterface
    {
        return new LexerState();
    }

    /**
     * @param TokenHandlerInterface[] $handlers
     */
    public static function createWithDefaultHandlers(
        array $handlers = [],
        ?ExpressionLexerInterface $expressionLexer = null,
        ?LexerStateInterface $state = null,
    ): static {
        return new static(
            handlers: \array_merge(
                self::getDefaultHandlers(),
                $handlers,
            ),
            expressionLexer: $expressionLexer ?? self::getDefaultExpressionLexer(),
            state: $state ?? self::getDefaultLexerState(),
        );
    }

    /**
     * @param TokenHandlerInterface[] $handlers
     */
    public static function createWithoutDefaultHandlers(
        array $handlers = [],
        ?ExpressionLexerInterface $expressionLexer = null,
        ?LexerStateInterface $state = null,
    ): static {
        return new static(
            handlers: $handlers,
            expressionLexer: $expressionLexer ?? self::getDefaultExpressionLexer(),
            state: $state ?? self::getDefaultLexerState(),
        );
    }

    /**
     * @return TokenInterface[]|null
     */
    private function tryMatchAndTokenize(
        ByteStreamInterface $stream,
    ): ?array {
        for ($i = 1; $i <= $this->maxTokenLength && !$stream->eof(); $i++) {
            $buffer = $stream->peek($i);
            $line = $stream->line;

            if (!\array_key_exists($buffer, $this->sequences)) {
                continue;
            } elseif (
                $stream->position > 0 &&
                $stream->input[$stream->position - 1] === '\\'
            ) {
                $stream->consumeSequence($buffer);

                return [
                    new TextToken(
                        line: $line,
                        op1: $buffer,
                    ),
                ];
            }

            $handler = $this->sequences[$buffer];
            $startSequence = $handler->getStartingSequence();
            $endSequence = $handler->getEndingSequence();

            $stream->consumeSequence($startSequence);

            $content = '';
            $startLine = $line;

            while (!$stream->eof()) {
                $offset = $stream->findSequenceOutsideQuotes($endSequence);

                if ($offset !== null) {
                    $content .= $stream->peek($offset);

                    $stream->consumeSequence($content);
                    $stream->consumeSequence($endSequence);

                    return $handler->tokenize(
                        startingLine: $startLine,
                        buffer: $content,
                        expressionLexer: $this->expressionLexer,
                        state: $this->state,
                    );
                }

                $content .= $stream->consume();
            }

            return [
                new TextToken(
                    line: $startLine,
                    op1: $startSequence . $content,
                ),
            ];
        }

        return null;
    }

    private function consumeTextUntilNextToken(
        ByteStreamInterface $stream,
    ): TokenInterface {
        $buffer = '';
        $line = $stream->line;

        while (!$stream->eof()) {
            for ($i = 1; $i <= $this->maxTokenLength && !$stream->eof(); $i++) {
                $peek = $stream->peek($i);

                if (isset($this->sequences[$peek])) {
                    return new TextToken(
                        line: $line,
                        op1: $buffer,
                    );
                }
            }

            $buffer .= $stream->consume();
        }

        return new TextToken(
            line: $line,
            op1: $buffer,
        );
    }

    /**
     * @throws LexerException
     */
    private function tokenize(
        ByteStreamInterface $stream,
    ): TokenStreamInterface {
        $tokens = [];

        while (!$stream->eof()) {
            $matchedTokens = $this->tryMatchAndTokenize($stream);

            if ($matchedTokens !== null) {
                $tokens = \array_merge(
                    $tokens,
                    $matchedTokens,
                );
            } else {
                $tokens[] = $this->consumeTextUntilNextToken($stream);
            }
        }

        return new TokenStream(
            tokens: $this->mergeAdjacentTextTokens($tokens),
        );
    }

    /**
     * @param TokenInterface[] $tokens
     * @return TokenInterface[]
     */
    private function mergeAdjacentTextTokens(array $tokens): array
    {
        $merged = [];
        $buffer = null;

        foreach ($tokens as $token) {
            if ($token instanceof TextToken) {
                if ($buffer === null) {
                    $buffer = $token;
                } else {
                    $buffered = $buffer->op1;

                    if (\array_key_exists($token->op1, $this->sequences)) {
                        $length = \mb_strlen($buffered);

                        if ($buffer->op1[$length - 1] === '\\') {
                            $buffered = \mb_substr($buffered, 0, -1);
                        }
                    }

                    $buffer = new TextToken(
                        line: $buffer->line,
                        op1: $buffered . $token->op1,
                    );
                }
            } else {
                if ($buffer !== null) {
                    $merged[] = $buffer;
                    $buffer = null;
                }

                $merged[] = $token;
            }
        }

        if ($buffer !== null) {
            $merged[] = $buffer;
        }

        return $merged;
    }


    public function tokenizeByString(
        string $sourceCode,
    ): TokenStreamInterface {
        return $this->tokenize(
            stream: ByteStream::createFromString($sourceCode),
        );
    }

    public function tokenizeByFile(
        string $sourceFile,
    ): TokenStreamInterface {
        return $this->tokenize(
            stream: ByteStream::createFromFile($sourceFile),
        );
    }
}
