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

namespace Tuxxedo\View\Lumi\Node;

use Tuxxedo\View\Lumi\Syntax\AssignmentOperator;

// @todo Check if we should make this into a ExpressionNodeInterface
readonly class AssignmentNode implements NodeInterface
{
    public string $kind;

    public function __construct(
        public IdentifierNode $name,
        public ExpressionNodeInterface $value,
        public ?AssignmentOperator $operator = null,
    ) {
        $this->kind = BuiltinNodeKinds::ROOT->name;
    }
}
