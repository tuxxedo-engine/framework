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
    'title' => 'Hřiště modelů',
    'renderedAt' => 'Vygenerováno: {date}',

    'action.new' => 'Nový',
    'action.table' => 'Tabulka',
    'action.metaData' => 'Metadata',
    'action.back' => 'Zpět',
    'action.show' => 'Zobrazit',
    'action.edit' => 'Upravit',
    'action.delete' => 'Smazat',
    'action.previous' => 'Předchozí',
    'action.next' => 'Další',
    'action.create' => 'Vytvořit',
    'action.update' => 'Uložit',
    'action.confirm' => 'Potvrdit',

    'field.id' => 'ID',
    'field.name' => 'Jméno',

    'answer.yes' => 'Ano',
    'answer.no' => 'Ne',

    'list.page' => 'Strana {page} z {pages}',
    'list.total.one' => 'Celkem {count} uživatel',
    'list.total.few' => 'Celkem {count} uživatelé',
    'list.total.many' => 'Celkem {count} uživatele',
    'list.total.other' => 'Celkem {count} uživatelů',

    'form.errors' => 'Uživatele nebylo možné uložit:',

    'delete.confirm' => 'Potvrďte prosím, že opravdu chcete smazat uživatele č. {id}',

    'meta.model' => 'Model',
    'meta.table' => 'Tabulka',
    'meta.primaryKeyColumn' => 'Sloupec primárního klíče',
    'meta.primaryKeyDeclaredOn' => '{column} (deklarováno na vlastnosti: {property})',
    'meta.primaryKeyAutoIncrement' => 'Automatický přírůstek primárního klíče?',
    'meta.readonly' => 'Je model označen jen pro čtení?',
    'meta.connection' => 'Databázové připojení',
    'meta.namedConnection' => 'Na pojmenovaném připojení: {connection}',
    'meta.managerConnection' => 'Na připojení správce modelů',

    'heading.compositeKey' => 'Složený klíč',
    'heading.columns' => 'Sloupce',
    'heading.identifiers' => 'Identifikátory',
    'heading.relations' => 'Relace',
    'heading.morphToRelations' => 'Relace MorphTo',
    'heading.aggregates' => 'Agregace',
    'heading.behaviors' => 'Chování',
    'heading.uniques' => 'Unikátní omezení',
    'heading.indexes' => 'Indexy',

    'column.property' => 'Vlastnost',
    'column.column' => 'Sloupec',
    'column.columns' => 'Sloupce',
    'column.nullable' => 'Může být NULL',
    'column.unique' => 'Unikátní',
    'column.readonly' => 'Jen pro čtení',
    'column.relatedClass' => 'Související třída',
    'column.typeColumn' => 'Sloupec typu',
    'column.idColumn' => 'Sloupec ID',
    'column.alias' => 'Alias',
    'column.relation' => 'Relace',
    'column.function' => 'Funkce',
    'column.slotType' => 'Typ slotu',
    'column.behaviorClass' => 'Třída chování',
];
