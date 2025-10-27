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

readonly class ForNode implements NodeInterface
{
    public array $scopes;

    /**
     * @param NodeInterface[] $body
     */
    public function __construct(
        public ExpressionNodeInterface $value,
        public IterableExpressionNodeInterface $iterator,
        public array $body = [],
        public ?IdentifierNode $key = null,
    ) {
        $this->scopes = [
            BuiltinNodeScopes::STATEMENT->name,
            BuiltinNodeScopes::BLOCK->name,
        ];
    }
}
