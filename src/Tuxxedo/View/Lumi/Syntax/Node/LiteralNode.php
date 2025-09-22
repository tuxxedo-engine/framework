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

use Tuxxedo\View\Lumi\Syntax\NativeType;

readonly class LiteralNode implements ExpressionNodeInterface
{
    public array $scopes;

    public function __construct(
        public string $operand,
        public NativeType $type,
    ) {
        $this->scopes = [
            BuiltinNodeScopes::EXPRESSION->name,
        ];
    }
}
