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

readonly class LumiNode implements NodeInterface
{
    public array $scopes;

    public function __construct(
        public string $theme,
        public string $sourceCode,
    ) {
        $this->scopes = [
            NodeScope::STATEMENT,
            NodeScope::BLOCK,
        ];
    }
}
