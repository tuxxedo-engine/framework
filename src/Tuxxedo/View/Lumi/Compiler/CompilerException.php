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
use Tuxxedo\View\Lumi\Syntax\Node\NodeScope;

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

    public static function fromUnexpectedStateEnter(
        NodeScope $scope,
    ): self {
        return new self(
            message: \sprintf(
                'Unexpected scope state entrance for "%s", old scope state must be left first',
                $scope->name,
            ),
        );
    }

    public static function fromUnexpectedStateLeave(
        NodeScope $scope,
    ): self {
        return new self(
            message: \sprintf(
                'Unexpected scope state leave for "%s", there is no scope state entrance for this',
                $scope->name,
            ),
        );
    }

    /**
     * @param NodeScope[] $scopes
     */
    public static function fromUnexpectedState(
        array $scopes,
        ?NodeScope $expects,
    ): self {
        return new self(
            message: \sprintf(
                'Unexpected scope state for node (can be: "%s"), expecting "%s"',
                \join(
                    '", "',
                    \array_map(
                        static fn (NodeScope $scope): string => $scope->name,
                        $scopes,
                    ),
                ),
                $expects->name ?? 'unknown',
            ),
        );
    }

    public static function fromCannotEscapePhp(): self
    {
        return new self(
            message: 'Unable to escape PHP style tags from node',
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
