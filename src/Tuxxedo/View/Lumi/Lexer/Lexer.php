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

use Tuxxedo\View\Lumi\ByteStream;
use Tuxxedo\View\Lumi\ByteStreamInterface;
use Tuxxedo\View\Lumi\Lexer\Handler\BlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\CommentHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\EchoHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\TokenHandlerInterface;
use Tuxxedo\View\Lumi\Token\TextToken;
use Tuxxedo\View\Lumi\Token\TokenInterface;

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
    public static function getDefaults(): array
    {
        return [
            new EchoHandler(),
            new CommentHandler(),
            new BlockHandler(),
        ];
    }

    /**
     * @param TokenHandlerInterface[] $handlers
     */
    public static function createWithDefaultHandlers(
        array $handlers = [],
        ?ExpressionLexerInterface $expressionLexer = null,
    ): static {
        return new static(
            handlers: \array_merge(
                self::getDefaults(),
                $handlers,
            ),
            expressionLexer: $expressionLexer ?? new ExpressionLexer(),
        );
    }

    /**
     * @param TokenHandlerInterface[] $handlers
     */
    public static function createWithoutDefaultHandlers(
        array $handlers = [],
        ?ExpressionLexerInterface $expressionLexer = null,
    ): static {
        return new static(
            handlers: $handlers,
            expressionLexer: $expressionLexer ?? new ExpressionLexer(),
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

            if (isset($this->sequences[$buffer])) {
                $handler = $this->sequences[$buffer];

                if (!$stream->peekSequence($handler->getEndingSequence(), $i)) {
                    $stream->consumeSequence($buffer);

                    return [
                        new TextToken($buffer),
                    ];
                }

                $stream->consumeSequence($buffer);

                return $handler->tokenize($stream, $this->expressionLexer);
            }
        }

        return null;
    }

    private function consumeTextUntilNextToken(
        ByteStreamInterface $stream,
    ): TokenInterface {
        $buffer = '';

        while (!$stream->eof()) {
            for ($i = 1; $i <= $this->maxTokenLength && !$stream->eof(); $i++) {
                $peek = $stream->peek($i);

                if (isset($this->sequences[$peek])) {
                    return new TextToken($buffer);
                }
            }

            $buffer .= $stream->consume();
        }

        return new TextToken($buffer);
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

        return new TokenStream($tokens);
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
