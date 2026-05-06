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

namespace Support\View\Lumi\Compiler;

use Tuxxedo\View\Lumi\Compiler\CompilerInterface;
use Tuxxedo\View\Lumi\Compiler\Expression\ExpressionCompilerInterface;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;

class RecordingExpressionCompiler implements ExpressionCompilerInterface
{
    public ?NodeStreamInterface $lastStream = null;
    public ?CompilerInterface $lastCompiler = null;

    public function __construct(
        public string $output = '/* expr */',
    ) {
    }

    public function compile(
        NodeStreamInterface $stream,
        CompilerInterface $compiler,
    ): string {
        $this->lastStream = $stream;
        $this->lastCompiler = $compiler;

        return $this->output;
    }
}
