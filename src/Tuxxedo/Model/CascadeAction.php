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

// @todo forceDelete cascade direction — forceDelete on a soft-deletable parent runs cascade through children's delete(), which routes soft-deletable children to softDelete() and leaves tombstones with FKs pointing at a gone parent. Propagate forceDelete intent through the cascade so force-deleted parents force-delete their cascade chain
enum CascadeAction
{
    case NO_ACTION;
    case CASCADE;
    case RESTRICT;
    case SET_NULL;
}
