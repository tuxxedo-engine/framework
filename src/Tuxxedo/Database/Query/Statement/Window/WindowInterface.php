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

interface WindowInterface
{
    /**
     * @var list<string>
     */
    public array $partitionColumns {
        get;
    }

    /**
     * @var list<array{column: string, direction: OrderDirection}>
     */
    public array $orderColumns {
        get;
    }

    public ?FrameUnit $frameUnit {
        get;
    }

    public ?FrameBoundInterface $frameStart {
        get;
    }

    public ?FrameBoundInterface $frameEnd {
        get;
    }

    public function partitionBy(
        string ...$columns,
    ): static;

    /**
     * @throws SqlException
     */
    public function orderBy(
        string $column,
        OrderDirection|string $direction = OrderDirection::ASC,
    ): static;

    /**
     * @throws SqlException
     */
    public function rowsBetween(
        FrameBoundInterface $start,
        FrameBoundInterface $end,
    ): static;

    /**
     * @throws SqlException
     */
    public function rangeBetween(
        FrameBoundInterface $start,
        FrameBoundInterface $end,
    ): static;
}
