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

readonly class ForNode implements NodeInterface
{
    public string $kind;

    /**
     * @param NodeInterface[] $body
     */
    public function __construct(
        public IterableExpressionNodeInterface $value,
        public ExpressionNodeInterface $iterator,
        public array $body = [],
        public ?IdentifierNode $key = null,
    ) {
        $this->kind = BuiltinNodeKinds::ROOT->name;
    }
}
