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

namespace Tuxxedo\Database\Query\Statement\CommonTable;

use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Statement\SubqueryRenderableInterface;
use Tuxxedo\Database\SqlException;

class CommonTableExpressions
{
    /**
     * @var list<array{name: string, query: SubqueryRenderableInterface, recursive: SubqueryRenderableInterface|null, columns: list<string>, distinct: bool}>
     */
    private array $expressions = [];

    /**
     * @param list<string> $columns
     *
     * @throws SqlException
     */
    public function add(
        string $name,
        SubqueryRenderableInterface $query,
        ?SubqueryRenderableInterface $recursive = null,
        array $columns = [],
        bool $distinct = false,
    ): void {
        foreach ($this->expressions as $expression) {
            if ($expression['name'] === $name) {
                throw SqlException::fromDuplicateCommonTableExpression(
                    name: $name,
                );
            }
        }

        $this->expressions[] = [
            'name' => $name,
            'query' => $query,
            'recursive' => $recursive,
            'columns' => $columns,
            'distinct' => $distinct,
        ];
    }

    /**
     * @return array{sql: string, parameters: array<string, string|int|float|bool|null|array<string|int|float|bool|null>>}
     *
     * @throws SqlException
     */
    public function render(
        DialectInterface $dialect,
        string $parameterPrefix,
    ): array {
        if (\sizeof($this->expressions) === 0) {
            return [
                'sql' => '',
                'parameters' => [],
            ];
        }

        $recursive = false;
        $parts = [];
        $parameters = [];

        foreach ($this->expressions as $index => $expression) {
            $prefix = $parameterPrefix . 'cte_' . $index . '_';
            $rendered = $expression['query']->renderSubquery(
                dialect: $dialect,
                parameterPrefix: $prefix . 'a_',
            );

            $body = $rendered['sql'];
            $parameters = [
                ...$parameters,
                ...$rendered['parameters'],
            ];

            if ($expression['recursive'] !== null) {
                $recursive = true;
                $member = $expression['recursive']->renderSubquery(
                    dialect: $dialect,
                    parameterPrefix: $prefix . 'r_',
                );

                $body .= ($expression['distinct']
                    ? ' UNION '
                    : ' UNION ALL ') . $member['sql'];

                $parameters = [
                    ...$parameters,
                    ...$member['parameters'],
                ];
            }

            $columns = \sizeof($expression['columns']) > 0
                ? ' (' . \join(', ', \array_map($dialect->identifier(...), $expression['columns'])) . ')'
                : '';

            $parts[] = $dialect->identifier($expression['name']) . $columns . ' AS (' . $body . ')';
        }

        return [
            'sql' => ($recursive
                ? 'WITH RECURSIVE '
                : 'WITH ') . \join(', ', $parts) . ' ',
            'parameters' => $parameters,
        ];
    }
}
