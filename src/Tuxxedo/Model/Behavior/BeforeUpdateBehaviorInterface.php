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

namespace Tuxxedo\Model\Behavior;

use Tuxxedo\Model\MetaData\ModelColumnInterface;

interface BeforeUpdateBehaviorInterface extends BehaviorInterface
{
    public function beforeUpdate(
        object $model,
        ModelColumnInterface $column,
    ): void;
}
