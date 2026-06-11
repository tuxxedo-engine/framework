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

namespace Tuxxedo\Model;

// @todo Soft-delete cascade — define interaction with the DeletedAt contract once audit columns land
// @todo Bulk-delete mode for HasMany cascade — opt-in single DELETE WHERE fk = parent_id instead of per-row recursion; skips grandchild cascade and future per-row events, so requires explicit declaration
enum CascadeAction
{
    case NO_ACTION;
    case CASCADE;
    case RESTRICT;
    case SET_NULL;
}
