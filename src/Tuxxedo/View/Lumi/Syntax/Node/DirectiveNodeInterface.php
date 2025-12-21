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

// @todo Optimizers operate on this interface, if another node were to exists that also implemented this, then
//       we would need to have some sort of hash/prefix, or perhaps $node::class? Consider a name slot here
interface DirectiveNodeInterface extends NodeInterface
{
    public LiteralNode $directive {
        get;
    }

    public LiteralNode $value {
        get;
    }
}
