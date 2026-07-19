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

namespace Support\Model;

class MysqlModelSchemaProvider implements ModelSchemaProvider
{
    public function countriesSchemaSql(): string
    {
        return 'CREATE TABLE countries (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'name VARCHAR(255) NOT NULL, ' .
            'code VARCHAR(2) NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function usersSchemaSql(): string
    {
        return 'CREATE TABLE users (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'name VARCHAR(255) NOT NULL, ' .
            'email VARCHAR(255) NOT NULL, ' .
            'isActive TINYINT(1) NOT NULL DEFAULT 1, ' .
            'postCount INT NOT NULL DEFAULT 0, ' .
            'score DOUBLE NOT NULL DEFAULT 0, ' .
            'country_id INT NULL, ' .
            'lastLoginAt VARCHAR(64) NULL, ' .
            'createdAt VARCHAR(64) NULL, ' .
            'updatedAt VARCHAR(64) NULL' .
            ')';
    }

    public function profilesSchemaSql(): string
    {
        return 'CREATE TABLE profiles (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'user_id INT NOT NULL, ' .
            'bio TEXT NOT NULL, ' .
            'avatar BLOB NULL, ' .
            'settings TEXT NULL, ' .
            'birthDate VARCHAR(64) NULL' .
            ')';
    }

    public function postsSchemaSql(): string
    {
        return 'CREATE TABLE posts (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'user_id INT NOT NULL, ' .
            'title VARCHAR(255) NOT NULL, ' .
            'body TEXT NOT NULL, ' .
            'status VARCHAR(32) NOT NULL DEFAULT \'draft\', ' .
            'publishedAt VARCHAR(64) NULL, ' .
            'viewCount BIGINT NOT NULL DEFAULT 0, ' .
            'rating DECIMAL(10, 2) NOT NULL DEFAULT \'0.00\'' .
            ')';
    }

    public function commentsSchemaSql(): string
    {
        return 'CREATE TABLE comments (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'post_id INT NOT NULL, ' .
            'user_id INT NOT NULL, ' .
            'body TEXT NOT NULL, ' .
            'createdAt VARCHAR(64) NULL, ' .
            'deletedAt VARCHAR(64) NULL' .
            ')';
    }

    public function tagsSchemaSql(): string
    {
        return 'CREATE TABLE tags (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'slug VARCHAR(255) NOT NULL, ' .
            'name VARCHAR(255) NOT NULL, ' .
            'category VARCHAR(255) NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function rolesSchemaSql(): string
    {
        return 'CREATE TABLE roles (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            '`key` VARCHAR(20) NOT NULL, ' .
            'label VARCHAR(100) NOT NULL, ' .
            'sortOrder TINYINT NOT NULL DEFAULT 0, ' .
            'startsAt VARCHAR(64) NULL' .
            ')';
    }

    public function categoriesSchemaSql(): string
    {
        return 'CREATE TABLE categories (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'parent_id INT NULL, ' .
            'name VARCHAR(100) NOT NULL, ' .
            'depth SMALLINT NOT NULL DEFAULT 0' .
            ')';
    }

    public function postTagPivotSchemaSql(): string
    {
        return 'CREATE TABLE post_tag (' .
            'post_id INT NOT NULL, ' .
            'tag_id INT NOT NULL, ' .
            'PRIMARY KEY (post_id, tag_id)' .
            ')';
    }

    public function userRolePivotSchemaSql(): string
    {
        return 'CREATE TABLE user_role (' .
            'user_id INT NOT NULL, ' .
            'role_id INT NOT NULL, ' .
            'PRIMARY KEY (user_id, role_id)' .
            ')';
    }

    public function sentinelsSchemaSql(): string
    {
        return 'CREATE TABLE sentinels (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'state VARCHAR(64) NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function cascadeGroupsSchemaSql(): string
    {
        return 'CREATE TABLE cascade_groups (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'name VARCHAR(255) NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function cascadeChildrenSchemaSql(): string
    {
        return 'CREATE TABLE cascade_children (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'auto_group_id INT NULL, ' .
            'restrict_group_id INT NULL, ' .
            'nullable_group_id INT NULL, ' .
            'noaction_group_id INT NULL, ' .
            'label VARCHAR(255) NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function cascadeHasOneChildrenSchemaSql(): string
    {
        return 'CREATE TABLE cascade_hasone_children (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'group_id INT NOT NULL, ' .
            'label VARCHAR(255) NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function cascadeHasOneRestrictChildrenSchemaSql(): string
    {
        return 'CREATE TABLE cascade_hasone_restrict_children (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'group_id INT NOT NULL, ' .
            'label VARCHAR(255) NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function cascadeTagsSchemaSql(): string
    {
        return 'CREATE TABLE cascade_tags (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'name VARCHAR(255) NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function cascadeGroupTagPivotSchemaSql(): string
    {
        return 'CREATE TABLE cascade_group_tag (' .
            'group_id INT NOT NULL, ' .
            'tag_id INT NOT NULL, ' .
            'PRIMARY KEY (group_id, tag_id)' .
            ')';
    }

    public function readonlyRecordsSchemaSql(): string
    {
        return 'CREATE TABLE readonly_records (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'name VARCHAR(255) NOT NULL' .
            ')';
    }

    public function settingsSchemaSql(): string
    {
        return 'CREATE TABLE settings (' .
            'scope VARCHAR(64) NOT NULL, ' .
            'name VARCHAR(64) NOT NULL, ' .
            'value TEXT NOT NULL, ' .
            'PRIMARY KEY (scope, name)' .
            ')';
    }

    public function bulkParentsSchemaSql(): string
    {
        return 'CREATE TABLE bulk_parents (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'name VARCHAR(255) NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function bulkChildrenSchemaSql(): string
    {
        return 'CREATE TABLE bulk_children (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'parent_id INT NOT NULL, ' .
            'label VARCHAR(255) NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function orphanParentsSchemaSql(): string
    {
        return 'CREATE TABLE orphan_parents (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'name VARCHAR(255) NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function orphanChildrenSchemaSql(): string
    {
        return 'CREATE TABLE orphan_children (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'parent_id INT NULL, ' .
            'label VARCHAR(255) NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function cascadeBelongsToParentsSchemaSql(): string
    {
        return 'CREATE TABLE cascade_bt_parents (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'name VARCHAR(255) NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function cascadeBelongsToChildrenSchemaSql(): string
    {
        return 'CREATE TABLE cascade_bt_children (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'parent_id INT NULL, ' .
            'label VARCHAR(255) NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function strictOwnersSchemaSql(): string
    {
        return 'CREATE TABLE strict_owners (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'name VARCHAR(255) NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function strictProfilesSchemaSql(): string
    {
        return 'CREATE TABLE strict_profiles (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'owner_id INT NOT NULL, ' .
            'handle VARCHAR(255) NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function strictChildrenSchemaSql(): string
    {
        return 'CREATE TABLE strict_children (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'owner_id INT NULL, ' .
            'label VARCHAR(255) NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function regionsSchemaSql(): string
    {
        return 'CREATE TABLE regions (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'name VARCHAR(255) NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function branchesSchemaSql(): string
    {
        return 'CREATE TABLE branches (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'region_id INT NOT NULL, ' .
            'warehouse_id INT NOT NULL' .
            ')';
    }

    public function warehousesSchemaSql(): string
    {
        return 'CREATE TABLE warehouses (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'name VARCHAR(255) NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function nullableThroughOwnersSchemaSql(): string
    {
        return 'CREATE TABLE nullable_through_owners (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'nullable_ref_id INT NULL' .
            ')';
    }

    public function strictThroughOwnersSchemaSql(): string
    {
        return 'CREATE TABLE strict_through_owners (' .
            'id INT AUTO_INCREMENT PRIMARY KEY, ' .
            'nullable_ref_id INT NULL' .
            ')';
    }
}
