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

namespace Tuxxedo\Database\Query\Builder;

interface JoinInterface
{
    public JoinType $type {
        get;
    }

    public string $identifier {
        get;
    }

    public string $first {
        get;
    }

    public string $second {
        get;
    }

    public ?JoinOperator $operator {
        get;
    }
}
