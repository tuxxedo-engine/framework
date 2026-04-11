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

interface BetweenConditionInterface
{
    public ConditionConjunction $conjunction {
        get;
    }

    public string $identifier {
        get;
    }

    public BetweenOperator $operator {
        get;
    }

    public string $from {
        get;
    }

    public string $to {
        get;
    }
}
