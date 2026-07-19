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

use Fixture\Model\Setting;
use Tuxxedo\Model\ModelException;

abstract class AbstractModelsManagerLifecycleIntegrationTestCase extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSettingsTable();
    }

    private function seedSetting(
        string $scope,
        string $name,
        string $value,
    ): void {
        $this->connection->insert(
            table: 'settings',
        )
            ->set(column: 'scope', value: $scope)
            ->set(column: 'name', value: $name)
            ->set(column: 'value', value: $value)
            ->execute();
    }

    private function readSettingValue(
        string $scope,
        string $name,
    ): mixed {
        $result = $this->connection->select(
            table: 'settings',
        )
            ->select('value')
            ->where(column: 'scope', value: $scope)
            ->where(column: 'name', value: $name)
            ->limit(1)
            ->execute();

        if (\count($result) === 0) {
            return null;
        }

        return $result->fetchAssoc()['value'] ?? null;
    }

    public function testCompositeKeyInsertPersistsAllColumns(): void
    {
        $setting = new Setting();
        $setting->scope = 'ui';
        $setting->name = 'theme';
        $setting->value = 'dark';

        (void) $this->modelsManager->save($setting);

        self::assertSame(
            'dark',
            $this->readSettingValue(
                scope: 'ui',
                name: 'theme',
            ),
        );
    }

    public function testFindByCompositeKeyReturnsMatchingRow(): void
    {
        $this->seedSetting(
            scope: 'audio',
            name: 'volume',
            value: '42',
        );

        $setting = $this->modelsManager->findByCompositeKey(
            class: Setting::class,
            keys: [
                'scope' => 'audio',
                'name' => 'volume',
            ],
        );

        self::assertInstanceOf(
            Setting::class,
            $setting,
        );

        self::assertSame(
            '42',
            $setting->value,
        );
    }

    public function testFindByCompositeKeyReturnsNullForUnknownKeys(): void
    {
        $result = $this->modelsManager->findByCompositeKey(
            class: Setting::class,
            keys: [
                'scope' => 'missing',
                'name' => 'nope',
            ],
        );

        self::assertNull(
            $result,
        );
    }

    public function testFetchByCompositeKeyThrowsWhenNotFound(): void
    {
        $this->expectException(ModelException::class);

        (void) $this->modelsManager->fetchByCompositeKey(
            class: Setting::class,
            keys: [
                'scope' => 'missing',
                'name' => 'nope',
            ],
        );
    }

    public function testCompositeKeyUpdatePersistsMutatedColumn(): void
    {
        $this->seedSetting(
            scope: 'ui',
            name: 'lang',
            value: 'en',
        );

        $setting = $this->modelsManager->fetchByCompositeKey(
            class: Setting::class,
            keys: [
                'scope' => 'ui',
                'name' => 'lang',
            ],
        );

        $setting->value = 'sv';

        (void) $this->modelsManager->save($setting);

        self::assertSame(
            'sv',
            $this->readSettingValue(
                scope: 'ui',
                name: 'lang',
            ),
        );
    }

    public function testCompositeKeyDeleteRemovesRow(): void
    {
        $this->seedSetting(
            scope: 'ephemeral',
            name: 'token',
            value: 'abc',
        );

        $setting = $this->modelsManager->fetchByCompositeKey(
            class: Setting::class,
            keys: [
                'scope' => 'ephemeral',
                'name' => 'token',
            ],
        );

        (void) $this->modelsManager->delete($setting);

        self::assertNull(
            $this->readSettingValue(
                scope: 'ephemeral',
                name: 'token',
            ),
        );
    }

    public function testSaveWithoutDirtyColumnsDoesNotFireUpdate(): void
    {
        $this->seedSetting(
            scope: 'ui',
            name: 'motion',
            value: 'original',
        );

        $setting = $this->modelsManager->fetchByCompositeKey(
            class: Setting::class,
            keys: [
                'scope' => 'ui',
                'name' => 'motion',
            ],
        );

        $this->connection->update(
            table: 'settings',
        )
            ->set(column: 'value', value: 'external')
            ->where(column: 'scope', value: 'ui')
            ->where(column: 'name', value: 'motion')
            ->execute();

        (void) $this->modelsManager->save($setting);

        self::assertSame(
            'external',
            $this->readSettingValue(
                scope: 'ui',
                name: 'motion',
            ),
        );
    }
}
