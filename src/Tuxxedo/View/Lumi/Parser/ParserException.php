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

namespace Tuxxedo\View\Lumi\Parser;

use Tuxxedo\View\Lumi\LumiException;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Operator\UnarySymbol;

class ParserException extends LumiException
{
    public static function fromUnexpectedToken(
        string $tokenName,
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Syntax error: Unexpected token "%s" on line %d',
                $tokenName,
                $line,
            ),
        );
    }

    /**
     * @param string[] $expectedTokenNames
     */
    public static function fromUnexpectedTokenWithExpectsOneOf(
        string $tokenName,
        array $expectedTokenNames,
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Syntax error: Unexpected token "%s", expected one of "%s" on line %d',
                $tokenName,
                \join('", "', $expectedTokenNames),
                $line,
            ),
        );
    }

    public static function fromNodeStreamEof(): self
    {
        return new self(
            message: 'Node stream has reached the end of stream unexpectedly',
        );
    }

    public static function fromUnexpectedLoopExit(): self
    {
        return new self(
            message: 'Cannot exit loop: no active loop context found',
        );
    }

    public static function fromUnexpectedConditionExit(): self
    {
        return new self(
            message: 'Cannot exit condition: no active conditional context found',
        );
    }

    public static function fromUnexpectedStackExit(): self
    {
        return new self(
            message: 'Cannot pop state stack, as the stack is empty',
        );
    }

    public static function fromMissingStateKey(
        string $key,
    ): self {
        return new self(
            message: \sprintf(
                'State key "%s" is not defined',
                $key,
            ),
        );
    }

    public static function fromUnexpectedStateType(
        string $key,
        string $type,
        string $expectedType,
    ): self {
        return new self(
            message: \sprintf(
                'Expected a %s value for key "%s", but received %s',
                $expectedType,
                $key,
                $type,
            ),
        );
    }

    public static function fromEmptyExpression(
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Expressions cannot be empty on line %d',
                $line,
            ),
        );
    }

    public static function fromUnexpectedContinueOutsideLoop(
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Continue cannot be used outside of loops on line %d',
                $line,
            ),
        );
    }

    public static function fromUnexpectedContinueOutOfBounds(
        int $level,
        int $maxLevel,
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot continue %d, current loop depth is %d on line %d',
                $level,
                $maxLevel,
                $line,
            ),
        );
    }

    public static function fromUnexpectedBreakOutsideLoop(
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Break cannot be used outside of loops on line %d',
                $line,
            ),
        );
    }

    public static function fromUnexpectedBreakOutOfBounds(
        int $level,
        int $maxLevel,
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot break %d, current loop depth is %d on line %d',
                $level,
                $maxLevel,
                $line,
            ),
        );
    }

    public static function fromExpressionNotIterable(
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'For or foreach iterator expression must be iterable on line %d',
                $line,
            ),
        );
    }

    public static function fromLayoutModeMustOnlyHaveOneLayout(
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'There must only be one layout rule per file on line %d',
                $line,
            ),
        );
    }

    public static function fromLayoutModeMustNotHaveRootElements(
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'A file with a layout rule must only contain block and whitespace at root level on line %d',
                $line,
            ),
        );
    }

    public static function fromLayoutModeMustBeRoot(
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'The layout rule must be in the root scope on line %d',
                $line,
            ),
        );
    }

    public static function fromBlockTokensCannotBeNested(
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Blocks must not contain inner blocks on line %d',
                $line,
            ),
        );
    }

    public static function fromDeclareTokensCannotBeNested(
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Declare must be at the top level on line %d',
                $line,
            ),
        );
    }

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

    public static function fromInvalidUnaryMutation(
        UnarySymbol $operator,
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid unary expression for "%s" encountered on line %d',
                $operator->symbol(),
                $line,
            ),
        );
    }
}
