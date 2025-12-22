<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Model;

/**
 * @template TModelClass of object
 */
interface ModelRepositoryInterface
{
    /**
     * @var class-string<TModelClass&ModelInterface>
     */
    public string $model {
        get;
    }

    /**
     * @return (TModelClass&ModelInterface)|null
     */
    public function findFirst(): ?ModelInterface;

    /**
     * @return (TModelClass&ModelInterface)|null
     */
    public function findLast(): ?ModelInterface;

    // @todo Exists
    // @todo Find
    // @todo Save
    // @todo Delete
    // @todo Query builder
}
