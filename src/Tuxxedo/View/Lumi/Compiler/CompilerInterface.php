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

namespace Tuxxedo\View\Lumi\Compiler;

use Tuxxedo\Escaper\EscaperInterface;
use Tuxxedo\View\Lumi\Compiler\Expression\ExpressionCompilerInterface;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;

interface CompilerInterface
{
    public ExpressionCompilerInterface $expressionCompiler {
        get;
    }

    public CompilerStateInterface $state {
        get;
    }

    public EscaperInterface $escaper {
        get;
    }

    /**
     * @throws CompilerException
     */
    public function compile(
        NodeStreamInterface $stream,
    ): string;

    public function compileNode(
        NodeInterface $node,
        NodeStreamInterface $stream,
    ): string;

    public function compileExpression(
        ExpressionNodeInterface $node,
    ): string;
}
