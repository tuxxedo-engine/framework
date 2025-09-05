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

namespace Tuxxedo\View\Lumi\Optimizer;

use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;

interface ScopeInterface
{
    /**
     * @var VariableInterface[]
     */
    public array $variables {
        get;
    }

    public function assign(
        AssignmentNode $node,
    ): void;

    public function get(
        string $name,
    ): VariableInterface;
}
