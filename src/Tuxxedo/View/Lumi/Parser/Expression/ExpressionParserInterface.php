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

namespace Tuxxedo\View\Lumi\Parser\Expression;

use Tuxxedo\View\Lumi\Lexer\TokenStreamInterface;
use Tuxxedo\View\Lumi\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Parser\ParserStateInterface;

interface ExpressionParserInterface
{
    public AtomicParserInterface $atomic {
        get;
    }

    public ArrayParserInterface $array {
        get;
    }

    public InvocationParserInterface $invocation {
        get;
    }

    public GroupingParserInterface $grouping {
        get;
    }

    public OperatorParserInterface $operator {
        get;
    }

    public TokenStreamInterface $stream {
        get;
    }

    public ParserStateInterface $state {
        get;
    }

    /**
     * @throws ParserException
     */
    public function parse(
        TokenStreamInterface $stream,
        ParserStateInterface $state,
    ): ExpressionNodeInterface;

    /**
     * @throws ParserException
     */
    public function dispatch(
        ?ExpressionParserDispatch $dispatch = null,
    ): ExpressionNodeInterface;
}
