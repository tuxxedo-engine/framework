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

namespace Tuxxedo\Database\Driver;

use Tuxxedo\Database\Query\Builder\CountBuilder;
use Tuxxedo\Database\Query\Builder\CountBuilderInterface;
use Tuxxedo\Database\Query\Builder\DeleteBuilder;
use Tuxxedo\Database\Query\Builder\DeleteBuilderInterface;
use Tuxxedo\Database\Query\Builder\ExistsBuilder;
use Tuxxedo\Database\Query\Builder\InsertBuilder;
use Tuxxedo\Database\Query\Builder\InsertBuilderInterface;
use Tuxxedo\Database\Query\Builder\InsertBulkBuilder;
use Tuxxedo\Database\Query\Builder\InsertBulkBuilderInterface;
use Tuxxedo\Database\Query\Builder\SelectBuilder;
use Tuxxedo\Database\Query\Builder\SelectBuilderInterface;
use Tuxxedo\Database\Query\Builder\Table\DropTableBuilder;
use Tuxxedo\Database\Query\Builder\Table\DropTableBuilderInterface;
use Tuxxedo\Database\Query\Builder\UpdateBuilder;
use Tuxxedo\Database\Query\Builder\UpdateBuilderInterface;
use Tuxxedo\Database\Query\Parser\StatementParserInterface;

abstract class AbstractConnection implements ConnectionInterface
{
    abstract protected StatementParserInterface $statementParser {
        get;
    }

    public function select(
        string $table,
    ): SelectBuilderInterface {
        return new SelectBuilder(
            connection: $this,
            table: $table,
            statementParser: $this->statementParser,
        );
    }

    public function insert(
        string $table,
    ): InsertBuilderInterface {
        return new InsertBuilder(
            connection: $this,
            table: $table,
            statementParser: $this->statementParser,
        );
    }

    public function insertBulk(
        string $table,
    ): InsertBulkBuilderInterface {
        return new InsertBulkBuilder(
            connection: $this,
            table: $table,
            statementParser: $this->statementParser,
        );
    }

    public function update(
        string $table,
    ): UpdateBuilderInterface {
        return new UpdateBuilder(
            connection: $this,
            table: $table,
            statementParser: $this->statementParser,
        );
    }

    public function delete(
        string $table,
    ): DeleteBuilderInterface {
        return new DeleteBuilder(
            connection: $this,
            table: $table,
            statementParser: $this->statementParser,
        );
    }

    public function exists(
        string $table,
    ): ExistsBuilder {
        return new ExistsBuilder(
            connection: $this,
            table: $table,
            statementParser: $this->statementParser,
        );
    }

    public function count(
        string $table,
    ): CountBuilderInterface {
        return new CountBuilder(
            connection: $this,
            table: $table,
            statementParser: $this->statementParser,
        );
    }

    public function dropTable(
        string $table,
    ): DropTableBuilderInterface {
        return new DropTableBuilder(
            connection: $this,
            table: $table,
            statementParser: $this->statementParser,
        );
    }
}
