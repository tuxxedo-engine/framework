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

// @todo Implement RESTRICT — block save/delete if dependent rows exist
// @todo Implement SET_NULL — null out child foreign keys (requires nullable FK on the relation column)
// @todo Soft-delete cascade — define interaction with the DeletedAt contract once audit columns land
enum CascadeAction
{
    case NO_ACTION;
    case CASCADE;
    case RESTRICT;
    case SET_NULL;
}
