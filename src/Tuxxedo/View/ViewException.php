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
        string $viewName,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot load view file: %s',
                $viewName,
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
        string $viewName,
    ): self {
        return new self(
            message: \sprintf(
                'Unable to determine view name for: %s',
                $viewName,
            ),
        );
    }
}
