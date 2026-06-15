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

use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Query\Builder\DeleteBuilder;
use Tuxxedo\Database\Query\Builder\DeleteBuilderInterface;
use Tuxxedo\Database\Query\Builder\ExistsBuilder;
use Tuxxedo\Database\Query\Builder\SelectBuilder;
use Tuxxedo\Database\Query\Builder\SelectBuilderInterface;
use Tuxxedo\Database\Query\Builder\UpdateBuilder;
use Tuxxedo\Database\Query\Builder\UpdateBuilderInterface;
use Tuxxedo\Database\Query\Statement\CountStatement;
use Tuxxedo\Database\Query\Statement\CountStatementInterface;
use Tuxxedo\Database\Query\Statement\InsertBulkStatement;
use Tuxxedo\Database\Query\Statement\InsertBulkStatementInterface;
use Tuxxedo\Database\Query\Statement\InsertStatement;
use Tuxxedo\Database\Query\Statement\InsertStatementInterface;
use Tuxxedo\Database\Query\Statement\Table\DropTableStatement;
use Tuxxedo\Database\Query\Statement\Table\DropTableStatementInterface;

abstract class AbstractConnection implements ConnectionInterface
{
    protected private(set) int $savepointCounter = 0;

    protected function generateSavepointName(): string
    {
        return \sprintf(
            'sp_%s_%d',
            \spl_object_id($this),
            $this->savepointCounter++,
        );
    }

    #[\NoDiscard]
    public function savepoint(): string
    {
        if (!$this->inTransaction()) {
            throw DatabaseException::fromSavepointOutsideTransaction();
        }

        $name = $this->generateSavepointName();

        $this->query(
            sql: \sprintf(
                'SAVEPOINT %s',
                $name,
            ),
            native: true,
        );

        return $name;
    }

    public function releaseSavepoint(
        string $name,
    ): void {
        $this->query(
            sql: \sprintf(
                'RELEASE SAVEPOINT %s',
                $name,
            ),
            native: true,
        );
    }

    public function rollbackToSavepoint(
        string $name,
    ): void {
        $this->query(
            sql: \sprintf(
                'ROLLBACK TO SAVEPOINT %s',
                $name,
            ),
            native: true,
        );
    }

    public function transaction(
        \Closure $transaction,
    ): mixed {
        try {
            $this->begin();

            $result = $transaction($this);

            $this->commit();

            return $result;
        } catch (\Exception $exception) {
            $this->rollback();

            throw $exception;
        }
    }

    public function nestedTransaction(
        \Closure $transaction,
    ): mixed {
        if (!$this->inTransaction()) {
            return $this->transaction($transaction);
        }

        $savepoint = $this->savepoint();

        try {
            $result = $transaction($this);

            $this->releaseSavepoint($savepoint);

            return $result;
        } catch (\Exception $exception) {
            $this->rollbackToSavepoint($savepoint);
            $this->releaseSavepoint($savepoint);

            throw $exception;
        }
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
    ): InsertStatementInterface {
        return new InsertStatement(
            table: $table,
            connection: $this,
        );
    }

    public function insertBulk(
        string $table,
    ): InsertBulkStatementInterface {
        return new InsertBulkStatement(
            table: $table,
            connection: $this,
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
    ): CountStatementInterface {
        return new CountStatement(
            table: $table,
            connection: $this,
        );
    }

    public function dropTable(
        string $table,
    ): DropTableStatementInterface {
        return new DropTableStatement(
            table: $table,
            connection: $this,
        );
    }
}
