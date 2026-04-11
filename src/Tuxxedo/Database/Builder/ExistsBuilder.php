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

namespace Tuxxedo\Database\Builder;

class ExistsBuilder extends AbstractWhereBuilder implements ExistsBuilderInterface
{
    public function exists(): bool
    {
        // @todo Implement

        return false;
    }
}
