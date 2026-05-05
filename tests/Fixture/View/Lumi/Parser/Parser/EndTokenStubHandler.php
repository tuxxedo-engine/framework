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
use Tuxxedo\View\Lumi\Syntax\Token\EndToken;

class EndTokenStubHandler implements ParserHandlerInterface
{
    public private(set) string $tokenClassName = EndToken::class;

    public function parse(
        ParserInterface $parser,
        TokenStreamInterface $stream,
    ): array {
        return [];
    }
}
