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

readonly class ArrayItemNode implements NodeInterface
{
    public array $scopes;

    public function __construct(
        public ExpressionNodeInterface $value,
        public ?ExpressionNodeInterface $key = null,
    ) {
        $this->scopes = [
            BuiltinNodeScopes::DEPENDANT->name,
            BuiltinNodeScopes::EXPRESSION->name,
        ];
    }
}
