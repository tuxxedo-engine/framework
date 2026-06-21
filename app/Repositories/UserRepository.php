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
use Tuxxedo\Model\Attribute\Connection\ModelDefaultConnection;
use Tuxxedo\Model\ModelsManagerInterface;

readonly class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        #[ModelDefaultConnection] private ModelsManagerInterface $modelsManager,
    ) {
    }

    public function findAll(): \Generator
    {
        yield from $this->modelsManager->query(User::class);
    }
}
