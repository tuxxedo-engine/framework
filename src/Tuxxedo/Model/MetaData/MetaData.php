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

namespace Tuxxedo\Model\MetaData;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DefaultInitializer;
use Tuxxedo\Model\MetaData\Adapter\MetaDataAdapterInterface;

#[DefaultInitializer(
    static function (ContainerInterface $container): MetaDataInterface {
        return new MetaData(
            adapter: $container->resolve(Adapter\ReflectionMetaDataAdapter::class),
        );
    },
)]
class MetaData implements MetaDataInterface
{
    /**
     * @var array<class-string, ModelMetaDataInterface>
     */
    private array $cachedModels = [];

    public function __construct(
        private readonly MetaDataAdapterInterface $adapter,
    ) {
    }

    public function getModel(
        string $model,
    ): ModelMetaDataInterface {
        if (\array_key_exists($model, $this->cachedModels)) {
            return $this->cachedModels[$model];
        }

        return $this->cachedModels[$model] = $this->adapter->getModel($model);
    }

    public function clearCache(): void
    {
        $this->cachedModels = [];
    }
}
