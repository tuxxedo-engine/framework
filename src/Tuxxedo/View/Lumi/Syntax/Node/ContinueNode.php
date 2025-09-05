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

readonly class ContinueNode implements NodeInterface
{
    public string $kind;

    public function __construct(
        public ?int $count = null,
    ) {
        $this->kind = BuiltinNodeKinds::ROOT->name;
    }
}
