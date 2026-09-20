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

interface ModelSchemaProvider
{
    public function countriesSchemaSql(): string;

    public function usersSchemaSql(): string;

    public function profilesSchemaSql(): string;

    public function postsSchemaSql(): string;

    public function commentsSchemaSql(): string;

    public function tagsSchemaSql(): string;

    public function rolesSchemaSql(): string;

    public function categoriesSchemaSql(): string;

    public function postTagPivotSchemaSql(): string;

    public function userRolePivotSchemaSql(): string;

    public function sentinelsSchemaSql(): string;

    public function cascadeGroupsSchemaSql(): string;

    public function cascadeChildrenSchemaSql(): string;

    public function cascadeHasOneChildrenSchemaSql(): string;

    public function cascadeHasOneRestrictChildrenSchemaSql(): string;

    public function cascadeTagsSchemaSql(): string;

    public function cascadeGroupTagPivotSchemaSql(): string;

    public function readonlyRecordsSchemaSql(): string;

    public function settingsSchemaSql(): string;

    public function bulkParentsSchemaSql(): string;

    public function bulkChildrenSchemaSql(): string;

    public function orphanParentsSchemaSql(): string;

    public function orphanChildrenSchemaSql(): string;

    public function cascadeBelongsToParentsSchemaSql(): string;

    public function cascadeBelongsToChildrenSchemaSql(): string;

    public function strictOwnersSchemaSql(): string;

    public function strictProfilesSchemaSql(): string;

    public function strictChildrenSchemaSql(): string;

    public function regionsSchemaSql(): string;

    public function branchesSchemaSql(): string;

    public function warehousesSchemaSql(): string;

    public function nullableThroughOwnersSchemaSql(): string;

    public function strictThroughOwnersSchemaSql(): string;

    public function articlesPolymorphicSchemaSql(): string;

    public function videosPolymorphicSchemaSql(): string;

    public function polyCommentsSchemaSql(): string;

    public function polyTagsSchemaSql(): string;

    public function polyTaggablesPivotSchemaSql(): string;

    public function employeesPolymorphicSchemaSql(): string;

    public function avatarsPolymorphicSchemaSql(): string;

    public function mappedArticlesSchemaSql(): string;

    public function mappedCommentsSchemaSql(): string;

    public function restrictArticlesSchemaSql(): string;

    public function nullableAvatarsSchemaSql(): string;

    public function setNullOwnersSchemaSql(): string;

    public function bulkDeleteBoardsSchemaSql(): string;

    public function orphanFeedsSchemaSql(): string;

    public function strictCommentsSchemaSql(): string;

    public function cascadeSaveCommentsSchemaSql(): string;

    public function plainAvatarsSchemaSql(): string;

    public function postersSchemaSql(): string;

    public function simpleHostsSchemaSql(): string;

    public function hostedCommentsSchemaSql(): string;

    public function strictPostersSchemaSql(): string;

    public function localKeyPostersSchemaSql(): string;

    public function compositeOwnersSchemaSql(): string;

    public function compositeOwnerHasOneChildrenSchemaSql(): string;

    public function compositeOwnerHasManyChildrenSchemaSql(): string;

    public function compositeChildOfSingleParentSchemaSql(): string;

    public function compositeTagsSchemaSql(): string;

    public function compositeOwnerTagsPivotSchemaSql(): string;

    public function compositeMorphNotesSchemaSql(): string;

    public function compositeMorphTagsPivotSchemaSql(): string;

    public function uuidV7OwnersSchemaSql(): string;

    public function uuidV7ChildrenSchemaSql(): string;

    public function ulidRecordsSchemaSql(): string;

    public function uuidV4ExplicitsSchemaSql(): string;
}
