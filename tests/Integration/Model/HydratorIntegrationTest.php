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

namespace Integration\Model;

use Fixture\Model\Category;
use Fixture\Model\Comment;
use Fixture\Model\Post;
use Fixture\Model\PostStatus;
use Fixture\Model\Profile;
use Fixture\Model\Role;
use Fixture\Model\Tag;
use Fixture\Model\User;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Model\Relation;

class HydratorIntegrationTest extends AbstractModelIntegrationTestCase
{
    public function testHydrateUserBasicScalarColumns(): void
    {
        $user = $this->modelsManager->hydrator->hydrate(
            className: User::class,
            values: [
                'id' => 1,
                'name' => 'Alice',
                'email' => 'alice@example.test',
                'isActive' => 1,
                'postCount' => 5,
                'score' => 12.5,
            ],
        );

        self::assertSame(
            1,
            $user->id,
        );

        self::assertSame(
            'Alice',
            $user->name,
        );

        self::assertSame(
            'alice@example.test',
            $user->email,
        );

        self::assertTrue(
            $user->isActive,
        );

        self::assertSame(
            5,
            $user->postCount,
        );

        self::assertSame(
            12.5,
            $user->score,
        );
    }

    public function testHydrateUserWithNullOptionalColumns(): void
    {
        $user = $this->modelsManager->hydrator->hydrate(
            className: User::class,
            values: [
                'id' => 1,
                'name' => 'Alice',
                'email' => '',
                'lastLoginAt' => null,
                'createdAt' => null,
                'updatedAt' => null,
            ],
        );

        self::assertNull(
            $user->lastLoginAt,
        );

        self::assertNull(
            $user->createdAt,
        );

        self::assertNull(
            $user->updatedAt,
        );
    }

    public function testHydrateUserWithMissingColumnPreservesDefault(): void
    {
        $user = $this->modelsManager->hydrator->hydrate(
            className: User::class,
            values: [
                'id' => 1,
                'name' => 'Alice',
            ],
        );

        self::assertSame(
            '',
            $user->email,
        );

        self::assertTrue(
            $user->isActive,
        );

        self::assertSame(
            0,
            $user->postCount,
        );

        self::assertSame(
            0.0,
            $user->score,
        );
    }

    public function testHydrateUserCreatedAtCoercedToDateTimeImmutable(): void
    {
        $user = $this->modelsManager->hydrator->hydrate(
            className: User::class,
            values: [
                'id' => 1,
                'name' => 'Alice',
                'createdAt' => '2026-07-16',
            ],
        );

        self::assertInstanceOf(
            \DateTimeImmutable::class,
            $user->createdAt,
        );

        self::assertSame(
            '2026-07-16',
            $user->createdAt->format('Y-m-d'),
        );
    }

    public function testHydrateUserAttachesHasOneProfileAsLazyProxyWhenIdPresent(): void
    {
        $user = $this->modelsManager->hydrator->hydrate(
            className: User::class,
            values: [
                'id' => 1,
                'name' => 'Alice',
            ],
        );

        self::assertInstanceOf(
            Profile::class,
            $user->profile,
        );
    }

    public function testHydrateUserAttachesHasOneProfileAsNullWhenIdIsNull(): void
    {
        $user = $this->modelsManager->hydrator->hydrate(
            className: User::class,
            values: [
                'id' => null,
                'name' => 'Alice',
            ],
        );

        self::assertNull(
            $user->profile,
        );
    }

    public function testHydrateUserAttachesHasManyPostsAsRelation(): void
    {
        $user = $this->modelsManager->hydrator->hydrate(
            className: User::class,
            values: [
                'id' => 1,
                'name' => 'Alice',
            ],
        );

        self::assertInstanceOf(
            Relation::class,
            $user->posts,
        );
    }

    public function testHydrateUserAttachesBelongsToManyRolesAsRelation(): void
    {
        $user = $this->modelsManager->hydrator->hydrate(
            className: User::class,
            values: [
                'id' => 1,
                'name' => 'Alice',
            ],
        );

        self::assertInstanceOf(
            Relation::class,
            $user->roles,
        );
    }

    public function testHydrateRecordsDirtySnapshotSoModelIsNotDirty(): void
    {
        $user = $this->modelsManager->hydrator->hydrate(
            className: User::class,
            values: [
                'id' => 1,
                'name' => 'Alice',
                'email' => 'alice@example.test',
            ],
        );

        self::assertTrue(
            $this->modelsManager->dirtyTracker->hasSnapshot($user),
        );

        $meta = $this->modelsManager->metaData->getModel(User::class);

        self::assertFalse(
            $this->modelsManager->dirtyTracker->isDirty(
                model: $user,
                metaData: $meta,
            ),
        );
    }

    public function testHydrateProfileWithForeignKeySnakeCaseColumn(): void
    {
        $profile = $this->modelsManager->hydrator->hydrate(
            className: Profile::class,
            values: [
                'id' => 1,
                'user_id' => 42,
                'bio' => 'Some bio',
            ],
        );

        self::assertSame(
            42,
            $profile->userId,
        );

        self::assertSame(
            'Some bio',
            $profile->bio,
        );
    }

    public function testHydrateProfileBlobRoundTrips(): void
    {
        $binary = "\x00\x01\x02\xff\xferaw bytes";

        $profile = $this->modelsManager->hydrator->hydrate(
            className: Profile::class,
            values: [
                'id' => 1,
                'user_id' => 1,
                'bio' => '',
                'avatar' => $binary,
            ],
        );

        self::assertSame(
            $binary,
            $profile->avatar,
        );
    }

    public function testHydrateProfileJsonColumnDecodedToAssocArray(): void
    {
        $profile = $this->modelsManager->hydrator->hydrate(
            className: Profile::class,
            values: [
                'id' => 1,
                'user_id' => 1,
                'bio' => '',
                'settings' => '{"theme":"dark","notifications":true}',
            ],
        );

        self::assertSame(
            [
                'theme' => 'dark',
                'notifications' => true,
            ],
            $profile->settings,
        );
    }

    public function testHydrateProfileDateColumnCoerced(): void
    {
        $profile = $this->modelsManager->hydrator->hydrate(
            className: Profile::class,
            values: [
                'id' => 1,
                'user_id' => 1,
                'bio' => '',
                'birthDate' => '1990-05-20',
            ],
        );

        self::assertInstanceOf(
            \DateTimeImmutable::class,
            $profile->birthDate,
        );

        self::assertSame(
            '1990-05-20',
            $profile->birthDate->format('Y-m-d'),
        );
    }

    public function testHydratePostEnumerationCoercedToEnumInstance(): void
    {
        $post = $this->modelsManager->hydrator->hydrate(
            className: Post::class,
            values: [
                'id' => 1,
                'user_id' => 1,
                'title' => 'Hello',
                'body' => '',
                'status' => 'published',
            ],
        );

        self::assertSame(
            PostStatus::PUBLISHED,
            $post->status,
        );
    }

    public function testHydratePostDecimalColumnPreservedAsString(): void
    {
        $post = $this->modelsManager->hydrator->hydrate(
            className: Post::class,
            values: [
                'id' => 1,
                'user_id' => 1,
                'title' => 'Hello',
                'body' => '',
                'rating' => '4.75',
            ],
        );

        self::assertSame(
            '4.75',
            $post->rating,
        );
    }

    public function testHydratePostBigIntegerColumnPreservedAsInt(): void
    {
        $post = $this->modelsManager->hydrator->hydrate(
            className: Post::class,
            values: [
                'id' => 1,
                'user_id' => 1,
                'title' => 'Hello',
                'body' => '',
                'viewCount' => 12345678901,
            ],
        );

        self::assertSame(
            12345678901,
            $post->viewCount,
        );
    }

    public function testHydrateCommentSoftDeleteColumnCoerced(): void
    {
        $comment = $this->modelsManager->hydrator->hydrate(
            className: Comment::class,
            values: [
                'id' => 1,
                'post_id' => 1,
                'user_id' => 1,
                'body' => '',
                'deletedAt' => '2026-07-16',
            ],
        );

        self::assertInstanceOf(
            \DateTimeImmutable::class,
            $comment->deletedAt,
        );
    }

    public function testHydrateRoleTimeColumnCoerced(): void
    {
        $role = $this->modelsManager->hydrator->hydrate(
            className: Role::class,
            values: [
                'id' => 1,
                'key' => 'ADMIN',
                'label' => 'Administrator',
                'sortOrder' => 10,
                'startsAt' => '09:00:00',
            ],
        );

        self::assertInstanceOf(
            \DateTimeImmutable::class,
            $role->startsAt,
        );

        self::assertSame(
            '09:00:00',
            $role->startsAt->format('H:i:s'),
        );
    }

    public function testHydrateRoleTinyIntegerAndCharPreserved(): void
    {
        $role = $this->modelsManager->hydrator->hydrate(
            className: Role::class,
            values: [
                'id' => 1,
                'key' => 'ADM',
                'label' => 'Admin',
                'sortOrder' => 100,
            ],
        );

        self::assertSame(
            'ADM',
            $role->key,
        );

        self::assertSame(
            100,
            $role->sortOrder,
        );
    }

    public function testHydrateTagIdentifierPreservedAsString(): void
    {
        $tag = $this->modelsManager->hydrator->hydrate(
            className: Tag::class,
            values: [
                'id' => 1,
                'slug' => 'php-tips',
                'name' => 'PHP Tips',
                'category' => 'DEV',
            ],
        );

        self::assertSame(
            'php-tips',
            $tag->slug,
        );

        self::assertSame(
            'DEV',
            $tag->category,
        );
    }

    public function testHydrateCategorySelfReferentialParentAttachment(): void
    {
        $category = $this->modelsManager->hydrator->hydrate(
            className: Category::class,
            values: [
                'id' => 5,
                'parent_id' => 2,
                'name' => 'Subcategory',
                'depth' => 1,
            ],
        );

        self::assertSame(
            2,
            $category->parentId,
        );

        self::assertInstanceOf(
            Relation::class,
            $category->children,
        );
    }

    public function testHydrateThrowsWhenNonScalarValueProvidedToCoercedColumn(): void
    {
        $this->expectException(ModelException::class);

        $this->modelsManager->hydrator->hydrate(
            className: Post::class,
            values: [
                'id' => 1,
                'user_id' => 1,
                'title' => 'Hello',
                'body' => '',
                'status' => new \stdClass(),
            ],
        );
    }
}
