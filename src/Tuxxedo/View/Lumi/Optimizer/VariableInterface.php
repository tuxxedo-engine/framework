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

use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;

interface VariableInterface
{
    public string $name {
        get;
    }

    public ExpressionNodeInterface $value {
        get;
    }

    public VariableLattice $state {
        get;
    }

    public function mutate(
        ScopeInterface $scope,
        ExpressionNodeInterface $value,
        AssignmentSymbol $operator = AssignmentSymbol::ASSIGN,
    ): void;
}
