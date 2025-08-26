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

namespace Tuxxedo\View\Lumi\Compiler;

use Tuxxedo\View\Lumi\Node\NodeInterface;

class CompilerException extends \Exception
{
    public static function fromCannotSave(
        string $name,
        string $path,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot save compiled view "%s" to: %s',
                $name,
                $path,
            ),
        );
    }

    /**
     * @param class-string<NodeInterface> $nodeClass
     */
    public static function fromUnexpectedNode(
        string $nodeClass,
    ): self {
        return new self(
            message: \sprintf(
                'Unexpected node "%s" encountered: No handler defined',
                $nodeClass,
            ),
        );
    }

    // @todo Remove
    public static function fromNotImplemented(
        string $feature,
    ): self {
        return new self(
            message: \sprintf(
                'The following feature is not implemented: %s',
                $feature,
            ),
        );
    }

    public static function fromCannotCallThis(): self
    {
        return new self(
            message: 'Calling $this in method calls is not allowed',
        );
    }

    public static function fromUnexpectedStateEnter(
        string $kind,
    ): self {
        return new self(
            message: \sprintf(
                'Unexpected state entrance for "%s", old state must be left first',
                $kind,
            ),
        );
    }

    public static function fromUnexpectedStateLeave(
        string $kind,
    ): self {
        return new self(
            message: \sprintf(
                'Unexpected state leave for "%s", there is no state entrance for this',
                $kind,
            ),
        );
    }

    public static function fromUnexpectedState(
        string $kind,
        string $expects,
    ): self {
        return new self(
            message: \sprintf(
                'Unexpected state for "%s", expecting "%s"',
                $kind,
                $expects,
            ),
        );
    }
}
