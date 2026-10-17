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

return [
    'title' => 'Model Playground',
    'renderedAt' => 'Rendered: {date}',

    'action.new' => 'New',
    'action.table' => 'Table',
    'action.metaData' => 'Meta Data',
    'action.back' => 'Back',
    'action.show' => 'Show',
    'action.edit' => 'Edit',
    'action.delete' => 'Delete',
    'action.previous' => 'Previous',
    'action.next' => 'Next',
    'action.create' => 'Create',
    'action.update' => 'Update',
    'action.confirm' => 'Confirm',

    'field.id' => 'ID',
    'field.name' => 'Name',

    'answer.yes' => 'Yes',
    'answer.no' => 'No',

    'list.page' => 'Page {page} of {pages}',
    'list.total.one' => '{count} user in total',
    'list.total.other' => '{count} users in total',

    'form.errors' => 'The user could not be saved:',

    'delete.confirm' => 'Please confirm whether you really wish to delete user #{id}',

    'meta.model' => 'Model',
    'meta.table' => 'Table',
    'meta.primaryKeyColumn' => 'Primary key column',
    'meta.primaryKeyDeclaredOn' => '{column} (declared on property: {property})',
    'meta.primaryKeyAutoIncrement' => 'Primary key auto increment?',
    'meta.readonly' => 'Is Model marked as readonly?',
    'meta.connection' => 'Database Connection',
    'meta.namedConnection' => 'On named connection: {connection}',
    'meta.managerConnection' => 'On the models manager connection',

    'heading.compositeKey' => 'Composite Key',
    'heading.columns' => 'Columns',
    'heading.identifiers' => 'Identifiers',
    'heading.relations' => 'Relations',
    'heading.morphToRelations' => 'MorphTo Relations',
    'heading.aggregates' => 'Aggregates',
    'heading.behaviors' => 'Behaviors',
    'heading.uniques' => 'Uniques',
    'heading.indexes' => 'Indexes',

    'column.property' => 'Property',
    'column.column' => 'Column',
    'column.columns' => 'Columns',
    'column.nullable' => 'Nullable',
    'column.unique' => 'Unique',
    'column.readonly' => 'Readonly',
    'column.relatedClass' => 'Related Class',
    'column.typeColumn' => 'Type Column',
    'column.idColumn' => 'Id Column',
    'column.alias' => 'Alias',
    'column.relation' => 'Relation',
    'column.function' => 'Function',
    'column.slotType' => 'Slot Type',
    'column.behaviorClass' => 'Behavior Class',
];
