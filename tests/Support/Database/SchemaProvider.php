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

namespace Support\Database;

interface SchemaProvider
{
    public function usersSchemaSql(): string;

    public function postsSchemaSql(): string;

    public function typesSchemaSql(): string;

    public function countersSchemaSql(): string;

    public function widgetsSchemaSql(): string;
}
