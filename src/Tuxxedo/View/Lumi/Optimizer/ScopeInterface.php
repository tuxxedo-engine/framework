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
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\PropertyAccessNode;

interface ScopeInterface
{
    /**
     * @var array<string, VariableInterface>
     */
    public array $variables {
        get;
    }

    public function assign(
        AssignmentNode $node,
    ): void;

    public function name(
        IdentifierNode|PropertyAccessNode $node,
    ): string;

    public function get(
        IdentifierNode|PropertyAccessNode|string $name,
    ): VariableInterface;
}
