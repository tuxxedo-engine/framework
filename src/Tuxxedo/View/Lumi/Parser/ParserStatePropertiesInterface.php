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

use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;

interface ParserStatePropertiesInterface
{
    public int $conditionDepth {
        get;
    }

    /**
     * @var string[]
     */
    public array $groupingStack {
        get;
    }

    /**
     * @var ExpressionNodeInterface[]
     */
    public array $nodeStack {
        get;
    }

    /**
     * @var array<string, string|int|bool>
     */
    public array $state {
        get;
    }
}
