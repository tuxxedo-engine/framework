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
                'Unexpected node %s encountered: no compiler handler defined',
                self::formatQuoted(self::formatShortClass($nodeClass)),
            ),
        );
    }

    public static function fromUnexpectedStateEnter(
        NodeScope $scope,
    ): self {
        return new self(
            message: \sprintf(
                'Unexpected scope state entry for %s, previous scope state must be left first',
                self::formatQuoted($scope->name),
            ),
        );
    }

    public static function fromUnexpectedStateLeave(
        NodeScope $scope,
    ): self {
        return new self(
            message: \sprintf(
                'Unexpected scope state exit for %s, no matching entry recorded',
                self::formatQuoted($scope->name),
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
                'Unexpected scope state for node (can be: %s), expecting %s',
                self::formatOneOf(
                    \array_map(
                        static fn (NodeScope $scope): string => $scope->name,
                        $scopes,
                    ),
                ),
                self::formatQuoted($expects->name ?? 'unknown'),
            ),
        );
    }

    /**
     * @codeCoverageIgnore
     */
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

    public static function fromNullPropertyAssignment(): self
    {
        return new self(
            message: 'Cannot use null-safe access in property assignments',
        );
    }

    public static function fromFunctionCallNotIdentifier(): self
    {
        return new self(
            message: 'Function calls must only use identifiers',
        );
    }

    public static function fromUnknownFilterCall(
        string $filter,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot pipe into "%s(...)": "%s" is not a filter; group the call for a bitwise OR, e.g. x | (%s(...))',
                $filter,
                $filter,
                $filter,
            ),
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function fromUnknownFilterFunction(
        string $filter,
        string $function,
    ): self {
        return new self(
            message: \sprintf(
                'Filter "%s" maps to the PHP function "%s", which does not exist',
                $filter,
                $function,
            ),
        );
    }

    public static function fromFilterArgumentCount(
        string $filter,
        int $given,
        int $minimum,
        ?int $maximum,
    ): self {
        return new self(
            message: \sprintf(
                'Filter "%s" accepts %s %s including the piped value, %d given',
                $filter,
                match (true) {
                    $maximum === null => 'at least ' . $minimum,
                    $maximum === $minimum => (string) $minimum,
                    default => $minimum . ' to ' . $maximum,
                },
                $maximum === 1
                    ? 'argument'
                    : 'arguments',
                $given,
            ),
        );
    }
}
