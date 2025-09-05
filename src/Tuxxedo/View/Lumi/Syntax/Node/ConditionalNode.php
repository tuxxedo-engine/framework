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

namespace Tuxxedo\View\Lumi\Syntax\Node;

readonly class ConditionalNode implements NodeInterface
{
    public string $kind;

    /**
     * @param NodeInterface[] $body
     * @param array<int, ConditionalBranchNode> $branches
     * @param NodeInterface[] $else
     */
    public function __construct(
        public ExpressionNodeInterface $operand,
        public array $body,
        public array $branches = [],
        public array $else = [],
    ) {
        $this->kind = BuiltinNodeKinds::ROOT->name;
    }
}
