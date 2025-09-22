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

namespace Tuxxedo\View;

class ViewException extends \Exception
{
    public static function fromViewNotFound(
        string $view,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot load view file: %s',
                $view,
            ),
        );
    }

    public static function fromViewRenderException(
        \Throwable $exception,
    ): self {
        return new self(
            message: \sprintf(
                'Unable to render view: %s: %s',
                $exception::class,
                $exception->getMessage(),
            ),
        );
    }

    public static function fromUnableToCaptureBuffer(): self
    {
        return new self(
            message: 'Unable to render view: Cannot capture output buffer',
        );
    }

    public static function fromUnableToDetermineViewName(
        string $view,
    ): self {
        return new self(
            message: \sprintf(
                'Unable to determine view name for: %s',
                $view,
            ),
        );
    }

    public static function fromFunctionCallsDisabled(): self
    {
        return new self(
            message: 'Cannot call function, as all global function calls are disabled',
        );
    }

    public static function fromCannotCallCustomFunction(
        string $function,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot call custom function "%s" as no handler has been implemented for it',
                $function,
            ),
        );
    }

    public static function fromCannotCallInstance(
        string $class,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot call instance of class "%s", as it has not explicitly been allowed',
                $class,
            ),
        );
    }

    public static function fromInvalidDirective(
        string $directive,
    ): self {
        return new self(
            message: \sprintf(
                'Directive "%s" is not declared and must be declared or have a default value before usage',
                $directive,
            ),
        );
    }

    public static function fromInvalidDirectiveType(
        string $directive,
        string $type,
        string $expectedType,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot fetch directive "%s" as "%s" (type is: "%s")',
                $directive,
                $expectedType,
                $type,
            ),
        );
    }

    public static function fromCannotCallCustomFunctionWithRender(): self
    {
        return new self(
            message: 'Cannot call custom function without a render object set',
        );
    }

    public static function fromUnableToPopDirectivesStack(): self
    {
        return new self(
            message: 'Cannot pop directives stack, likely stack corruption',
        );
    }

    public static function fromUnknownFilterCall(
        string $filter,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot call unknown filter "%s"',
                $filter,
            ),
        );
    }

    public static function fromInvalidBitwiseOr(
        string $leftType,
        string $rightType,
    ): self {
        return new self(
            message: \sprintf(
                'Both operands must be integers for bitwise OR, left was "%s" and right was "%s"',
                $leftType,
                $rightType,
            ),
        );
    }

    public static function fromCannotAccessThis(): self
    {
        return new self(
            message: 'Accessing $this in views is not allowed',
        );
    }

    public static function fromInvalidBlock(
        string $name,
    ): self {
        return new self(
            message: \sprintf(
                'Unable to invoke unknown block "%s"',
                $name,
            ),
        );
    }
}
