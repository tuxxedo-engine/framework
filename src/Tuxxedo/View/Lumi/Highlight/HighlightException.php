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

namespace Tuxxedo\View\Lumi\Highlight;

use Tuxxedo\View\Lumi\LumiException;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;

class HighlightException extends LumiException
{
    public static function fromUnknownHighlightNode(
        NodeInterface $node,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot highlight source, unknown node encountered: %s',
                $node::class,
            ),
        );
    }

    public static function fromInvalidTheme(
        string $theme,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot highlight source, as the theme "%s" does not exist',
                $theme,
            ),
        );
    }
}
