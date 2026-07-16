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

namespace Unit\Model\MetaData\Adapter;

use Fixture\Model\Category;
use Fixture\Model\Comment;
use Fixture\Model\Country;
use Fixture\Model\Post;
use Fixture\Model\PostStatus;
use Fixture\Model\Profile;
use Fixture\Model\Role;
use Fixture\Model\Tag;
use Fixture\Model\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Model\Attribute\Column\DeletedAt;
use Tuxxedo\Model\Attribute\Relation\BelongsTo;
use Tuxxedo\Model\Attribute\Relation\BelongsToMany;
use Tuxxedo\Model\Attribute\Relation\HasMany;
use Tuxxedo\Model\Attribute\Relation\HasManyThrough;
use Tuxxedo\Model\Attribute\Relation\HasOne;
use Tuxxedo\Model\Attribute\Relation\HasOneThrough;
use Tuxxedo\Model\Behavior\CreatedAtBehavior;
use Tuxxedo\Model\Behavior\DeletedAtBehavior;
use Tuxxedo\Model\Behavior\UpdatedAtBehavior;
use Tuxxedo\Model\MetaData\Adapter\ReflectionMetaDataAdapter;
use Tuxxedo\Model\MetaData\ModelColumnInterface;
use Tuxxedo\Model\MetaData\ModelPrimaryKeyInterface;
use Tuxxedo\Model\MetaData\ModelRelationInterface;
use Tuxxedo\Model\ModelException;

class ReflectionMetaDataAdapterTest extends TestCase
{
    private ReflectionMetaDataAdapter $adapter;

    protected function setUp(): void
    {
        $this->adapter = new ReflectionMetaDataAdapter();
    }

    public function testUserTableAndModelClass(): void
    {
        $meta = $this->adapter->getModel(User::class);

        self::assertSame(
            User::class,
            $meta->model,
        );

        self::assertSame(
            'users',
            $meta->table,
        );
    }

    public function testUserPrimaryKeyDetectedAsAutoIncrementIdColumn(): void
    {
        $meta = $this->adapter->getModel(User::class);

        self::assertInstanceOf(
            ModelPrimaryKeyInterface::class,
            $meta->key,
        );

        self::assertSame(
            'id',
            $meta->key->property,
        );

        self::assertSame(
            'id',
            $meta->key->column,
        );

        self::assertTrue(
            $meta->key->autoIncrement,
        );
    }

    public function testUserColumnsListPresentAndCorrectlyMapped(): void
    {
        $meta = $this->adapter->getModel(User::class);
        $columnsByProperty = self::indexColumnsByProperty($meta->columns);

        self::assertArrayHasKey(
            'id',
            $columnsByProperty,
        );

        self::assertArrayHasKey(
            'name',
            $columnsByProperty,
        );

        self::assertArrayHasKey(
            'email',
            $columnsByProperty,
        );

        self::assertArrayHasKey(
            'isActive',
            $columnsByProperty,
        );

        self::assertArrayHasKey(
            'postCount',
            $columnsByProperty,
        );

        self::assertArrayHasKey(
            'score',
            $columnsByProperty,
        );

        self::assertArrayHasKey(
            'lastLoginAt',
            $columnsByProperty,
        );

        self::assertArrayHasKey(
            'createdAt',
            $columnsByProperty,
        );

        self::assertArrayHasKey(
            'updatedAt',
            $columnsByProperty,
        );

        self::assertSame(
            'name',
            $columnsByProperty['name']->column,
        );

        self::assertSame(
            'isActive',
            $columnsByProperty['isActive']->column,
        );
    }

    public function testUserBehaviorsWireCreatedAtAndUpdatedAt(): void
    {
        $meta = $this->adapter->getModel(User::class);

        self::assertArrayHasKey(
            'createdAt',
            $meta->behaviors,
        );

        self::assertSame(
            CreatedAtBehavior::class,
            $meta->behaviors['createdAt'],
        );

        self::assertArrayHasKey(
            'updatedAt',
            $meta->behaviors,
        );

        self::assertSame(
            UpdatedAtBehavior::class,
            $meta->behaviors['updatedAt'],
        );
    }

    public function testUserRelationsIncludeProfilePostsAndRoles(): void
    {
        $meta = $this->adapter->getModel(User::class);
        $relationsByProperty = self::indexRelationsByProperty($meta->relations);

        self::assertArrayHasKey(
            'profile',
            $relationsByProperty,
        );

        self::assertInstanceOf(
            HasOne::class,
            $relationsByProperty['profile']->attribute,
        );

        self::assertSame(
            Profile::class,
            $relationsByProperty['profile']->relatedClass,
        );

        self::assertArrayHasKey(
            'posts',
            $relationsByProperty,
        );

        self::assertInstanceOf(
            HasMany::class,
            $relationsByProperty['posts']->attribute,
        );

        self::assertSame(
            Post::class,
            $relationsByProperty['posts']->relatedClass,
        );

        self::assertArrayHasKey(
            'roles',
            $relationsByProperty,
        );

        self::assertInstanceOf(
            BelongsToMany::class,
            $relationsByProperty['roles']->attribute,
        );

        self::assertSame(
            Role::class,
            $relationsByProperty['roles']->relatedClass,
        );

        self::assertArrayHasKey(
            'country',
            $relationsByProperty,
        );

        self::assertInstanceOf(
            BelongsTo::class,
            $relationsByProperty['country']->attribute,
        );

        self::assertSame(
            Country::class,
            $relationsByProperty['country']->relatedClass,
        );
    }

    public function testUserCountryIdSnakeCaseColumnMapping(): void
    {
        $meta = $this->adapter->getModel(User::class);
        $columnsByProperty = self::indexColumnsByProperty($meta->columns);

        self::assertArrayHasKey(
            'countryId',
            $columnsByProperty,
        );

        self::assertSame(
            'country_id',
            $columnsByProperty['countryId']->column,
        );
    }

    public function testCountryHasManyThroughAndHasOneThroughRelationsWired(): void
    {
        $meta = $this->adapter->getModel(Country::class);
        $relationsByProperty = self::indexRelationsByProperty($meta->relations);

        self::assertArrayHasKey(
            'users',
            $relationsByProperty,
        );

        self::assertInstanceOf(
            HasMany::class,
            $relationsByProperty['users']->attribute,
        );

        self::assertArrayHasKey(
            'posts',
            $relationsByProperty,
        );

        self::assertInstanceOf(
            HasManyThrough::class,
            $relationsByProperty['posts']->attribute,
        );

        self::assertArrayHasKey(
            'firstPost',
            $relationsByProperty,
        );

        self::assertInstanceOf(
            HasOneThrough::class,
            $relationsByProperty['firstPost']->attribute,
        );
    }

    public function testProfileForeignKeySnakeCaseColumnMapping(): void
    {
        $meta = $this->adapter->getModel(Profile::class);
        $columnsByProperty = self::indexColumnsByProperty($meta->columns);

        self::assertArrayHasKey(
            'userId',
            $columnsByProperty,
        );

        self::assertSame(
            'user_id',
            $columnsByProperty['userId']->column,
        );
    }

    public function testProfileBelongsToUser(): void
    {
        $meta = $this->adapter->getModel(Profile::class);
        $relationsByProperty = self::indexRelationsByProperty($meta->relations);

        self::assertArrayHasKey(
            'user',
            $relationsByProperty,
        );

        self::assertInstanceOf(
            BelongsTo::class,
            $relationsByProperty['user']->attribute,
        );

        self::assertSame(
            User::class,
            $relationsByProperty['user']->relatedClass,
        );
    }

    public function testPostEnumerationColumnCarriesEnumClassInCoercerArguments(): void
    {
        $meta = $this->adapter->getModel(Post::class);
        $columnsByProperty = self::indexColumnsByProperty($meta->columns);

        self::assertArrayHasKey(
            'status',
            $columnsByProperty,
        );

        self::assertSame(
            PostStatus::class,
            $columnsByProperty['status']->attribute->coercerArguments['enum'],
        );
    }

    public function testPostForeignKeyMapsToSnakeCase(): void
    {
        $meta = $this->adapter->getModel(Post::class);
        $columnsByProperty = self::indexColumnsByProperty($meta->columns);

        self::assertSame(
            'user_id',
            $columnsByProperty['userId']->column,
        );
    }

    public function testCommentSoftDeleteBehaviorWiredToDeletedAtColumn(): void
    {
        $meta = $this->adapter->getModel(Comment::class);

        self::assertArrayHasKey(
            'deletedAt',
            $meta->behaviors,
        );

        self::assertSame(
            DeletedAtBehavior::class,
            $meta->behaviors['deletedAt'],
        );

        $deletedAtColumn = $meta->columnFor(property: 'deletedAt');

        self::assertNotNull(
            $deletedAtColumn,
        );

        self::assertInstanceOf(
            DeletedAt::class,
            $deletedAtColumn->attribute,
        );
    }

    public function testCommentTwoBelongsToRelations(): void
    {
        $meta = $this->adapter->getModel(Comment::class);
        $relationsByProperty = self::indexRelationsByProperty($meta->relations);

        self::assertArrayHasKey(
            'post',
            $relationsByProperty,
        );

        self::assertSame(
            Post::class,
            $relationsByProperty['post']->relatedClass,
        );

        self::assertArrayHasKey(
            'author',
            $relationsByProperty,
        );

        self::assertSame(
            User::class,
            $relationsByProperty['author']->relatedClass,
        );
    }

    public function testTagIdentifierDetectedOnSlug(): void
    {
        $meta = $this->adapter->getModel(Tag::class);

        self::assertNotSame(
            [],
            $meta->identifiers,
        );

        $columns = \array_map(
            static fn ($identifier): string => $identifier->column,
            $meta->identifiers,
        );

        self::assertContains(
            'slug',
            $columns,
        );
    }

    public function testCategorySelfReferentialRelations(): void
    {
        $meta = $this->adapter->getModel(Category::class);
        $relationsByProperty = self::indexRelationsByProperty($meta->relations);

        self::assertArrayHasKey(
            'parent',
            $relationsByProperty,
        );

        self::assertSame(
            Category::class,
            $relationsByProperty['parent']->relatedClass,
        );

        self::assertArrayHasKey(
            'children',
            $relationsByProperty,
        );

        self::assertSame(
            Category::class,
            $relationsByProperty['children']->relatedClass,
        );
    }

    public function testCategoryParentIdMapsToSnakeCase(): void
    {
        $meta = $this->adapter->getModel(Category::class);
        $columnsByProperty = self::indexColumnsByProperty($meta->columns);

        self::assertSame(
            'parent_id',
            $columnsByProperty['parentId']->column,
        );
    }

    public function testCachePerFixtureReturnsIdenticalInstance(): void
    {
        $first = $this->adapter->getModel(User::class);
        $second = $this->adapter->getModel(User::class);

        self::assertNotSame(
            $first,
            $second,
        );
    }

    public function testBehaviorsOfCreatedAtBehaviorFiltersCorrectly(): void
    {
        $meta = $this->adapter->getModel(User::class);

        $created = $meta->behaviorsOf(behavior: CreatedAtBehavior::class);

        self::assertArrayHasKey(
            'createdAt',
            $created,
        );

        self::assertArrayNotHasKey(
            'updatedAt',
            $created,
        );
    }

    public function testColumnForKnownPropertyReturnsColumn(): void
    {
        $meta = $this->adapter->getModel(User::class);

        $column = $meta->columnFor(property: 'email');

        self::assertNotNull(
            $column,
        );

        self::assertSame(
            'email',
            $column->column,
        );
    }

    public function testColumnForUnknownPropertyReturnsNull(): void
    {
        $meta = $this->adapter->getModel(User::class);

        self::assertNull(
            $meta->columnFor(property: 'nonexistent'),
        );
    }

    /**
     * @return \Generator<array{0: class-string}>
     */
    public static function invalidModelClassDataProvider(): \Generator
    {
        yield [
            \Traversable::class,
        ];

        yield [
            PostStatus::class,
        ];
    }

    /**
     * @param class-string $modelClass
     */
    #[DataProvider('invalidModelClassDataProvider')]
    public function testGetModelRejectsInvalidClass(
        string $modelClass,
    ): void {
        $this->expectException(ModelException::class);

        $this->adapter->getModel(
            model: $modelClass,
        );
    }

    /**
     * @param ModelColumnInterface[] $columns
     * @return array<string, ModelColumnInterface>
     */
    private static function indexColumnsByProperty(
        array $columns,
    ): array {
        $indexed = [];

        foreach ($columns as $column) {
            $indexed[$column->property] = $column;
        }

        return $indexed;
    }

    /**
     * @param ModelRelationInterface[] $relations
     * @return array<string, ModelRelationInterface>
     */
    private static function indexRelationsByProperty(
        array $relations,
    ): array {
        $indexed = [];

        foreach ($relations as $relation) {
            $indexed[$relation->property] = $relation;
        }

        return $indexed;
    }
}
