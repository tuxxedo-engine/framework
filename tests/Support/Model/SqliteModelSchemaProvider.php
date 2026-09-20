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

class SqliteModelSchemaProvider implements ModelSchemaProvider
{
    public function countriesSchemaSql(): string
    {
        return 'CREATE TABLE countries (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'name TEXT NOT NULL, ' .
            'code TEXT NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function usersSchemaSql(): string
    {
        return 'CREATE TABLE users (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'name TEXT NOT NULL, ' .
            'email TEXT NOT NULL, ' .
            'isActive INTEGER NOT NULL DEFAULT 1, ' .
            'postCount INTEGER NOT NULL DEFAULT 0, ' .
            'score REAL NOT NULL DEFAULT 0, ' .
            'country_id INTEGER NULL, ' .
            'lastLoginAt TEXT NULL, ' .
            'createdAt TEXT NULL, ' .
            'updatedAt TEXT NULL' .
            ')';
    }

    public function profilesSchemaSql(): string
    {
        return 'CREATE TABLE profiles (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'user_id INTEGER NOT NULL, ' .
            'bio TEXT NOT NULL DEFAULT \'\', ' .
            'avatar BLOB NULL, ' .
            'settings TEXT NULL, ' .
            'birthDate TEXT NULL' .
            ')';
    }

    public function postsSchemaSql(): string
    {
        return 'CREATE TABLE posts (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'user_id INTEGER NOT NULL, ' .
            'title TEXT NOT NULL, ' .
            'body TEXT NOT NULL DEFAULT \'\', ' .
            'status TEXT NOT NULL DEFAULT \'draft\', ' .
            'publishedAt TEXT NULL, ' .
            'viewCount INTEGER NOT NULL DEFAULT 0, ' .
            'rating TEXT NOT NULL DEFAULT \'0.00\'' .
            ')';
    }

    public function commentsSchemaSql(): string
    {
        return 'CREATE TABLE comments (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'post_id INTEGER NOT NULL, ' .
            'user_id INTEGER NOT NULL, ' .
            'body TEXT NOT NULL DEFAULT \'\', ' .
            'createdAt TEXT NULL, ' .
            'deletedAt TEXT NULL' .
            ')';
    }

    public function tagsSchemaSql(): string
    {
        return 'CREATE TABLE tags (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'slug TEXT NOT NULL, ' .
            'name TEXT NOT NULL, ' .
            'category TEXT NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function rolesSchemaSql(): string
    {
        return 'CREATE TABLE roles (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            '"key" TEXT NOT NULL, ' .
            'label TEXT NOT NULL, ' .
            'sortOrder INTEGER NOT NULL DEFAULT 0, ' .
            'startsAt TEXT NULL' .
            ')';
    }

    public function categoriesSchemaSql(): string
    {
        return 'CREATE TABLE categories (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'parent_id INTEGER NULL, ' .
            'name TEXT NOT NULL, ' .
            'depth INTEGER NOT NULL DEFAULT 0' .
            ')';
    }

    public function postTagPivotSchemaSql(): string
    {
        return 'CREATE TABLE post_tag (' .
            'post_id INTEGER NOT NULL, ' .
            'tag_id INTEGER NOT NULL, ' .
            'PRIMARY KEY (post_id, tag_id)' .
            ')';
    }

    public function userRolePivotSchemaSql(): string
    {
        return 'CREATE TABLE user_role (' .
            'user_id INTEGER NOT NULL, ' .
            'role_id INTEGER NOT NULL, ' .
            'PRIMARY KEY (user_id, role_id)' .
            ')';
    }

    public function sentinelsSchemaSql(): string
    {
        return 'CREATE TABLE sentinels (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'state TEXT NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function cascadeGroupsSchemaSql(): string
    {
        return 'CREATE TABLE cascade_groups (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'name TEXT NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function cascadeChildrenSchemaSql(): string
    {
        return 'CREATE TABLE cascade_children (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'auto_group_id INTEGER NULL, ' .
            'restrict_group_id INTEGER NULL, ' .
            'nullable_group_id INTEGER NULL, ' .
            'noaction_group_id INTEGER NULL, ' .
            'label TEXT NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function cascadeHasOneChildrenSchemaSql(): string
    {
        return 'CREATE TABLE cascade_hasone_children (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'group_id INTEGER NOT NULL, ' .
            'label TEXT NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function cascadeHasOneRestrictChildrenSchemaSql(): string
    {
        return 'CREATE TABLE cascade_hasone_restrict_children (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'group_id INTEGER NOT NULL, ' .
            'label TEXT NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function cascadeTagsSchemaSql(): string
    {
        return 'CREATE TABLE cascade_tags (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'name TEXT NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function cascadeGroupTagPivotSchemaSql(): string
    {
        return 'CREATE TABLE cascade_group_tag (' .
            'group_id INTEGER NOT NULL, ' .
            'tag_id INTEGER NOT NULL, ' .
            'PRIMARY KEY (group_id, tag_id)' .
            ')';
    }

    public function readonlyRecordsSchemaSql(): string
    {
        return 'CREATE TABLE readonly_records (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'name TEXT NOT NULL' .
            ')';
    }

    public function settingsSchemaSql(): string
    {
        return 'CREATE TABLE settings (' .
            'scope TEXT NOT NULL, ' .
            'name TEXT NOT NULL, ' .
            'value TEXT NOT NULL DEFAULT \'\', ' .
            'PRIMARY KEY (scope, name)' .
            ')';
    }

    public function bulkParentsSchemaSql(): string
    {
        return 'CREATE TABLE bulk_parents (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'name TEXT NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function bulkChildrenSchemaSql(): string
    {
        return 'CREATE TABLE bulk_children (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'parent_id INTEGER NOT NULL, ' .
            'label TEXT NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function orphanParentsSchemaSql(): string
    {
        return 'CREATE TABLE orphan_parents (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'name TEXT NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function orphanChildrenSchemaSql(): string
    {
        return 'CREATE TABLE orphan_children (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'parent_id INTEGER NULL, ' .
            'label TEXT NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function cascadeBelongsToParentsSchemaSql(): string
    {
        return 'CREATE TABLE cascade_bt_parents (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'name TEXT NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function cascadeBelongsToChildrenSchemaSql(): string
    {
        return 'CREATE TABLE cascade_bt_children (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'parent_id INTEGER NULL, ' .
            'label TEXT NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function strictOwnersSchemaSql(): string
    {
        return 'CREATE TABLE strict_owners (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'name TEXT NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function strictProfilesSchemaSql(): string
    {
        return 'CREATE TABLE strict_profiles (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'owner_id INTEGER NOT NULL, ' .
            'handle TEXT NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function strictChildrenSchemaSql(): string
    {
        return 'CREATE TABLE strict_children (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'owner_id INTEGER NULL, ' .
            'label TEXT NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function regionsSchemaSql(): string
    {
        return 'CREATE TABLE regions (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'name TEXT NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function branchesSchemaSql(): string
    {
        return 'CREATE TABLE branches (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'region_id INTEGER NOT NULL, ' .
            'warehouse_id INTEGER NOT NULL' .
            ')';
    }

    public function warehousesSchemaSql(): string
    {
        return 'CREATE TABLE warehouses (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'name TEXT NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function nullableThroughOwnersSchemaSql(): string
    {
        return 'CREATE TABLE nullable_through_owners (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'nullable_ref_id INTEGER NULL' .
            ')';
    }

    public function strictThroughOwnersSchemaSql(): string
    {
        return 'CREATE TABLE strict_through_owners (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'nullable_ref_id INTEGER NULL' .
            ')';
    }

    public function articlesPolymorphicSchemaSql(): string
    {
        return 'CREATE TABLE articles (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'user_id INTEGER NOT NULL, ' .
            'title TEXT NOT NULL' .
            ')';
    }

    public function videosPolymorphicSchemaSql(): string
    {
        return 'CREATE TABLE videos (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'user_id INTEGER NOT NULL, ' .
            'title TEXT NOT NULL' .
            ')';
    }

    public function polyCommentsSchemaSql(): string
    {
        return 'CREATE TABLE poly_comments (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'commentable_type TEXT NOT NULL, ' .
            'commentable_id INTEGER NOT NULL, ' .
            'body TEXT NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function polyTagsSchemaSql(): string
    {
        return 'CREATE TABLE poly_tags (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'name TEXT NOT NULL' .
            ')';
    }

    public function polyTaggablesPivotSchemaSql(): string
    {
        return 'CREATE TABLE poly_taggables (' .
            'tag_id INTEGER NOT NULL, ' .
            'taggable_type TEXT NOT NULL, ' .
            'taggable_id INTEGER NOT NULL, ' .
            'PRIMARY KEY (tag_id, taggable_type, taggable_id)' .
            ')';
    }

    public function employeesPolymorphicSchemaSql(): string
    {
        return 'CREATE TABLE employees (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'name TEXT NOT NULL' .
            ')';
    }

    public function avatarsPolymorphicSchemaSql(): string
    {
        return 'CREATE TABLE avatars (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'subject_type TEXT NOT NULL, ' .
            'subject_id INTEGER NOT NULL, ' .
            'url TEXT NOT NULL' .
            ')';
    }

    public function mappedArticlesSchemaSql(): string
    {
        return 'CREATE TABLE mapped_articles (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'title TEXT NOT NULL' .
            ')';
    }

    public function mappedCommentsSchemaSql(): string
    {
        return 'CREATE TABLE mapped_comments (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'commentable_type TEXT NOT NULL, ' .
            'commentable_id INTEGER NOT NULL, ' .
            'body TEXT NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function restrictArticlesSchemaSql(): string
    {
        return 'CREATE TABLE restrict_articles (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'title TEXT NOT NULL' .
            ')';
    }

    public function nullableAvatarsSchemaSql(): string
    {
        return 'CREATE TABLE nullable_avatars (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'subject_type TEXT NULL, ' .
            'subject_id INTEGER NULL, ' .
            'url TEXT NOT NULL' .
            ')';
    }

    public function setNullOwnersSchemaSql(): string
    {
        return 'CREATE TABLE set_null_owners (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'name TEXT NOT NULL' .
            ')';
    }

    public function bulkDeleteBoardsSchemaSql(): string
    {
        return 'CREATE TABLE bulk_delete_boards (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'name TEXT NOT NULL' .
            ')';
    }

    public function orphanFeedsSchemaSql(): string
    {
        return 'CREATE TABLE orphan_feeds (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'name TEXT NOT NULL' .
            ')';
    }

    public function strictCommentsSchemaSql(): string
    {
        return 'CREATE TABLE strict_comments (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'commentable_type TEXT NOT NULL, ' .
            'commentable_id INTEGER NOT NULL, ' .
            'body TEXT NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function cascadeSaveCommentsSchemaSql(): string
    {
        return 'CREATE TABLE cascade_save_comments (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'commentable_type TEXT NOT NULL, ' .
            'commentable_id INTEGER NOT NULL, ' .
            'body TEXT NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function plainAvatarsSchemaSql(): string
    {
        return 'CREATE TABLE plain_avatars (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'subject_type TEXT NOT NULL, ' .
            'subject_id INTEGER NOT NULL, ' .
            'url TEXT NOT NULL' .
            ')';
    }

    public function postersSchemaSql(): string
    {
        return 'CREATE TABLE posters (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'name TEXT NOT NULL' .
            ')';
    }

    public function simpleHostsSchemaSql(): string
    {
        return 'CREATE TABLE simple_hosts (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'name TEXT NOT NULL' .
            ')';
    }

    public function hostedCommentsSchemaSql(): string
    {
        return 'CREATE TABLE hosted_comments (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'host_type TEXT NOT NULL, ' .
            'host_id INTEGER NOT NULL, ' .
            'body TEXT NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function strictPostersSchemaSql(): string
    {
        return 'CREATE TABLE strict_posters (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'name TEXT NOT NULL' .
            ')';
    }

    public function localKeyPostersSchemaSql(): string
    {
        return 'CREATE TABLE localkey_posters (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'external_id INTEGER NOT NULL, ' .
            'name TEXT NOT NULL' .
            ')';
    }

    public function compositeOwnersSchemaSql(): string
    {
        return 'CREATE TABLE composite_owners (' .
            'scope TEXT NOT NULL, ' .
            'name TEXT NOT NULL, ' .
            'label TEXT NOT NULL DEFAULT \'\', ' .
            'PRIMARY KEY (scope, name)' .
            ')';
    }

    public function compositeOwnerHasOneChildrenSchemaSql(): string
    {
        return 'CREATE TABLE composite_owner_hasone_children (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'owner_scope TEXT NOT NULL, ' .
            'owner_name TEXT NOT NULL, ' .
            'label TEXT NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function compositeOwnerHasManyChildrenSchemaSql(): string
    {
        return 'CREATE TABLE composite_owner_hasmany_children (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'owner_scope TEXT NOT NULL, ' .
            'owner_name TEXT NOT NULL, ' .
            'label TEXT NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function compositeChildOfSingleParentSchemaSql(): string
    {
        return 'CREATE TABLE composite_child_of_single_parent (' .
            'scope TEXT NOT NULL, ' .
            'name TEXT NOT NULL, ' .
            'user_id INTEGER NOT NULL, ' .
            'label TEXT NOT NULL DEFAULT \'\', ' .
            'PRIMARY KEY (scope, name)' .
            ')';
    }

    public function compositeTagsSchemaSql(): string
    {
        return 'CREATE TABLE composite_tags (' .
            'realm TEXT NOT NULL, ' .
            'code TEXT NOT NULL, ' .
            'label TEXT NOT NULL DEFAULT \'\', ' .
            'PRIMARY KEY (realm, code)' .
            ')';
    }

    public function compositeOwnerTagsPivotSchemaSql(): string
    {
        return 'CREATE TABLE composite_owner_tags (' .
            'owner_scope TEXT NOT NULL, ' .
            'owner_name TEXT NOT NULL, ' .
            'tag_realm TEXT NOT NULL, ' .
            'tag_code TEXT NOT NULL, ' .
            'PRIMARY KEY (owner_scope, owner_name, tag_realm, tag_code)' .
            ')';
    }

    public function compositeMorphNotesSchemaSql(): string
    {
        return 'CREATE TABLE composite_morph_notes (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'ownerType TEXT NULL, ' .
            'ownerScope TEXT NULL, ' .
            'ownerName TEXT NULL, ' .
            'body TEXT NOT NULL DEFAULT \'\'' .
            ')';
    }

    public function compositeMorphTagsPivotSchemaSql(): string
    {
        return 'CREATE TABLE composite_morph_tags (' .
            'owner_type TEXT NOT NULL, ' .
            'owner_scope TEXT NOT NULL, ' .
            'owner_name TEXT NOT NULL, ' .
            'tag_realm TEXT NOT NULL, ' .
            'tag_code TEXT NOT NULL, ' .
            'PRIMARY KEY (owner_type, owner_scope, owner_name, tag_realm, tag_code)' .
            ')';
    }
}
