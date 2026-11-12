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

namespace Tuxxedo\Logger;

class MemoryLogger extends AbstractLogger implements \Countable
{
    /**
     * @var list<LogRecordInterface>
     */
    public private(set) array $records = [];

    protected function writeRecord(
        LogRecordInterface $record,
    ): void {
        $this->records[] = $record;
    }

    public function count(): int
    {
        return \sizeof($this->records);
    }
}
