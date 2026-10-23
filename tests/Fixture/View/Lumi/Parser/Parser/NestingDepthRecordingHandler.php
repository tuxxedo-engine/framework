<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2026 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Fixture\View\Lumi\Parser\Parser;

use Tuxxedo\View\Lumi\Lexer\TokenStreamInterface;
use Tuxxedo\View\Lumi\Parser\Handler\ParserHandlerInterface;
use Tuxxedo\View\Lumi\Parser\ParserInterface;

class NestingDepthRecordingHandler implements ParserHandlerInterface
{
    public private(set) string $tokenClassName = FooToken::class;

    /**
     * @var list<int>
     */
    public private(set) array $depths = [];

    public function parse(
        ParserInterface $parser,
        TokenStreamInterface $stream,
    ): array {
        $stream->consume();

        $this->depths[] = $parser->state->nestingDepth;

        return [
            new FooNode(),
        ];
    }
}
