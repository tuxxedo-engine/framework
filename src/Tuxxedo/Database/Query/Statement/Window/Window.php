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

use Tuxxedo\Database\Query\Statement\Order\OrderDirection;
use Tuxxedo\Database\SqlException;

class Window implements WindowInterface
{
    /**
     * @var list<string>
     */
    public private(set) array $partitionColumns = [];

    /**
     * @var list<array{column: string, direction: OrderDirection}>
     */
    public private(set) array $orderColumns = [];

    public private(set) ?FrameUnit $frameUnit = null;
    public private(set) ?FrameBoundInterface $frameStart = null;
    public private(set) ?FrameBoundInterface $frameEnd = null;

    public function partitionBy(
        string ...$columns,
    ): static {
        foreach ($columns as $column) {
            $this->partitionColumns[] = $column;
        }

        return $this;
    }

    public function orderBy(
        string $column,
        OrderDirection|string $direction = OrderDirection::ASC,
    ): static {
        if (\is_string($direction)) {
            $direction = OrderDirection::fromInput($direction);
        }

        $this->orderColumns[] = [
            'column' => $column,
            'direction' => $direction,
        ];

        return $this;
    }

    public function rowsBetween(
        FrameBoundInterface $start,
        FrameBoundInterface $end,
    ): static {
        return $this->frame(
            unit: FrameUnit::ROWS,
            start: $start,
            end: $end,
        );
    }

    public function rangeBetween(
        FrameBoundInterface $start,
        FrameBoundInterface $end,
    ): static {
        return $this->frame(
            unit: FrameUnit::RANGE,
            start: $start,
            end: $end,
        );
    }

    /**
     * @throws SqlException
     */
    private function frame(
        FrameUnit $unit,
        FrameBoundInterface $start,
        FrameBoundInterface $end,
    ): static {
        if (
            $start->kind === FrameBoundKind::UNBOUNDED_FOLLOWING ||
            $end->kind === FrameBoundKind::UNBOUNDED_PRECEDING ||
            self::position($start) > self::position($end)
        ) {
            throw SqlException::fromInvalidWindowFrame(
                start: $start->kind,
                end: $end->kind,
            );
        }

        $this->frameUnit = $unit;
        $this->frameStart = $start;
        $this->frameEnd = $end;

        return $this;
    }

    private static function position(
        FrameBoundInterface $bound,
    ): int {
        return match ($bound->kind) {
            FrameBoundKind::UNBOUNDED_PRECEDING => \PHP_INT_MIN,
            FrameBoundKind::PRECEDING => -($bound->offset ?? 0),
            FrameBoundKind::CURRENT_ROW => 0,
            FrameBoundKind::FOLLOWING => $bound->offset ?? 0,
            FrameBoundKind::UNBOUNDED_FOLLOWING => \PHP_INT_MAX,
        };
    }
}
