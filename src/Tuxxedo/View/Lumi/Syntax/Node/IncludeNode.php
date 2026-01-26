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

readonly class IncludeNode implements NodeInterface
{
    public array $scopes;

    public function __construct(
        public ExpressionNodeInterface $file,
        public ?ArrayNode $scope,
    ) {
        $this->scopes = [
            NodeScope::STATEMENT,
        ];
    }
}
