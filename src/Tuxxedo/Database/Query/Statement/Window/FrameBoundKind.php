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

enum FrameBoundKind
{
    case UNBOUNDED_PRECEDING;
    case PRECEDING;
    case CURRENT_ROW;
    case FOLLOWING;
    case UNBOUNDED_FOLLOWING;
}
