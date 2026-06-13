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

// @todo Soft-delete cascade semantics — when a soft-deletable parent is deleted, cascade currently invokes children's normal delete() which may hard-delete non-soft-delete children; decide whether soft-delete should skip cascade or restrict it to soft-deletable children. Same question for forceDelete: cascade currently calls children's delete(), not forceDelete()
enum CascadeAction
{
    case NO_ACTION;
    case CASCADE;
    case RESTRICT;
    case SET_NULL;
}
