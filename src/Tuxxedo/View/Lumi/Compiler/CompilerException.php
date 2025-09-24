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

use Tuxxedo\View\Lumi\LumiException;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;

class CompilerException extends LumiException
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

    public static function fromCannotCallThis(): self
    {
        return new self(
            message: 'Calling $this in method calls is not allowed',
        );
    }

    public static function fromCannotOverrideThis(): self
    {
        return new self(
            message: 'Overriding $this is not allowed',
        );
    }

    public static function fromCannotWriteThis(): self
    {
        return new self(
            message: 'Writing to $this is not allowed',
        );
    }

    public static function fromUnexpectedStateEnter(
        string $scope,
    ): self {
        return new self(
            message: \sprintf(
                'Unexpected scope state entrance for "%s", old scope state must be left first',
                $scope,
            ),
        );
    }

    public static function fromUnexpectedStateLeave(
        string $scope,
    ): self {
        return new self(
            message: \sprintf(
                'Unexpected scope state leave for "%s", there is no scope state entrance for this',
                $scope,
            ),
        );
    }

    /**
     * @param string[] $scopes
     */
    public static function fromUnexpectedState(
        array $scopes,
        string $expects,
    ): self {
        return new self(
            message: \sprintf(
                'Unexpected scope state for node (can be: "%s"), expecting "%s"',
                \join('", "', $scopes),
                $expects,
            ),
        );
    }

    public static function fromCannotEscapePhp(): self
    {
        return new self(
            message: 'Unable to escape PHP style tags from node',
        );
    }

    public static function fromCannotEscapeQuote(): self
    {
        return new self(
            message: 'Unable to escape single quotes from node',
        );
    }

    public static function fromArrayAccessWithoutKey(): self
    {
        return new self(
            message: 'Array access must have a key in read context',
        );
    }

    public static function fromCannotPopOptimizerScope(): self
    {
        return new self(
            message: 'Cannot pop optimizer scope, possible optimizer corruption',
        );
    }

    public static function fromNullPropertyAssignment(): self
    {
        return new self(
            message: 'Cannot use null-safe access in property assignments',
        );
    }

    public static function fromFunctionCallNotIdentifier(): self
    {
        return new self(
            message: 'Function calls must only be using identifiers',
        );
    }
}
