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

namespace App\Repositories;

use App\Models\User;
use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;

#[DefaultImplementation(class: UserRepository::class, lifecycle: Lifecycle::PERSISTENT)]
interface UserRepositoryInterface
{
    /**
     * @return \Generator<int, User>
     */
    public function findAll(): \Generator;
}
