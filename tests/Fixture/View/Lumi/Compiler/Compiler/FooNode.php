<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2026 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Fixture\View\Lumi\Compiler\Compiler;

use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\NodeScope;

readonly class FooNode implements NodeInterface
{
    public array $scopes;

    public function __construct()
    {
        $this->scopes = [
            NodeScope::STATEMENT,
            NodeScope::BLOCK,
        ];
    }
}
