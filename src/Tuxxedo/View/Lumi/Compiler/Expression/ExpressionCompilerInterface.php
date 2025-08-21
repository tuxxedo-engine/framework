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

namespace Tuxxedo\View\Lumi\Compiler\Expression;

use Tuxxedo\View\Lumi\Compiler\CompilerException;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;

interface ExpressionCompilerInterface
{
    /**
     * @throws CompilerException
     */
    public function compile(
        NodeStreamInterface $stream,
    ): string;
}
