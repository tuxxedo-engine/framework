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

namespace Tuxxedo\Database\Query\Statement\Window;

use Tuxxedo\Database\SqlException;

class FrameBound implements FrameBoundInterface
{
    /**
     * @param non-negative-int|null $offset
     */
    private function __construct(
        public readonly FrameBoundKind $kind,
        public readonly ?int $offset = null,
    ) {
    }

    public static function unboundedPreceding(): self
    {
        return new self(
            kind: FrameBoundKind::UNBOUNDED_PRECEDING,
        );
    }

    /**
     * @throws SqlException
     */
    public static function preceding(
        int $offset,
    ): self {
        if ($offset < 0) {
            throw SqlException::fromNegativeFrameOffset(
                offset: $offset,
            );
        }

        return new self(
            kind: FrameBoundKind::PRECEDING,
            offset: $offset,
        );
    }

    public static function currentRow(): self
    {
        return new self(
            kind: FrameBoundKind::CURRENT_ROW,
        );
    }

    /**
     * @throws SqlException
     */
    public static function following(
        int $offset,
    ): self {
        if ($offset < 0) {
            throw SqlException::fromNegativeFrameOffset(
                offset: $offset,
            );
        }

        return new self(
            kind: FrameBoundKind::FOLLOWING,
            offset: $offset,
        );
    }

    public static function unboundedFollowing(): self
    {
        return new self(
            kind: FrameBoundKind::UNBOUNDED_FOLLOWING,
        );
    }
}
