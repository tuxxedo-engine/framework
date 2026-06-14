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

namespace Tuxxedo\Http\Request\Context;

use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\UploadedFile;
use Tuxxedo\Http\UploadedFileInterface;

/**
 * @phpstan-type phpUploadedFileArray array{name: string, type: string, size: int, error: int, tmp_name: string, full_path: string}
 */
class EnvironmentUploadedFilesContext implements UploadedFilesContextInterface
{
    /**
     * @var array<array-key, mixed>|null
     */
    private ?array $normalized = null;

    /**
     * @return array<array-key, mixed>
     */
    private function tree(): array
    {
        if ($this->normalized !== null) {
            return $this->normalized;
        }

        $result = [];

        foreach ($_FILES as $key => $entry) {
            $result[$key] = $this->normalizeNode($entry);
        }

        $this->normalized = $result;

        return $result;
    }

    private function normalizeNode(
        mixed $node,
    ): mixed {
        if (!\is_array($node)) {
            return $node;
        }

        if (
            \array_key_exists('name', $node) &&
            \array_key_exists('error', $node) &&
            \is_array($node['name']) &&
            \is_array($node['error'])
        ) {
            return $this->unzip($node);
        }

        if (
            \array_key_exists('name', $node) &&
            \is_string($node['name']) &&
            \array_key_exists('error', $node) &&
            \is_int($node['error'])
        ) {
            return $node;
        }

        $result = [];

        foreach ($node as $key => $child) {
            $result[$key] = $this->normalizeNode($child);
        }

        return $result;
    }

    /**
     * @param array<array-key, mixed> $entry
     * @return array<array-key, mixed>
     */
    private function unzip(
        array $entry,
    ): array {
        $result = [];

        if (!isset($entry['name']) || !\is_array($entry['name'])) {
            return $result;
        }

        foreach (\array_keys($entry['name']) as $key) {
            $sub = [
                'name' => $entry['name'][$key] ?? null,
                'type' => (\is_array($entry['type'] ?? null) ? ($entry['type'][$key] ?? null) : null),
                'size' => (\is_array($entry['size'] ?? null) ? ($entry['size'][$key] ?? null) : null),
                'error' => (\is_array($entry['error'] ?? null) ? ($entry['error'][$key] ?? null) : null),
                'tmp_name' => (\is_array($entry['tmp_name'] ?? null) ? ($entry['tmp_name'][$key] ?? null) : null),
                'full_path' => (\is_array($entry['full_path'] ?? null) ? ($entry['full_path'][$key] ?? null) : null),
            ];

            if (
                \is_string($sub['name']) &&
                \is_string($sub['type']) &&
                \is_int($sub['size']) &&
                \is_int($sub['error']) &&
                \is_string($sub['tmp_name']) &&
                \is_string($sub['full_path'])
            ) {
                /** @var phpUploadedFileArray $sub */
                $result[$key] = $sub;

                continue;
            }

            $result[$key] = $this->unzip($sub);
        }

        return $result;
    }

    private function resolve(
        string $path,
    ): mixed {
        $node = $this->tree();

        foreach (\explode('.', $path) as $segment) {
            if (!\is_array($node) || !\array_key_exists($segment, $node)) {
                return null;
            }

            $node = $node[$segment];
        }

        return $node;
    }

    private function isLeaf(
        mixed $node,
    ): bool {
        return \is_array($node) &&
            \array_key_exists('name', $node) &&
            \is_string($node['name']) &&
            \array_key_exists('error', $node) &&
            \is_int($node['error']);
    }

    /**
     * @param mixed[] $node
     */
    private function isListOfLeaves(
        array $node,
    ): bool {
        if (!\array_is_list($node)) {
            return false;
        }

        foreach ($node as $entry) {
            if (!$this->isLeaf($entry)) {
                return false;
            }
        }

        return true;
    }

    public function has(
        string $path,
    ): bool {
        $node = $this->resolve($path);

        if ($this->isLeaf($node)) {
            return true;
        }

        if (\is_array($node) && $this->isListOfLeaves($node)) {
            return true;
        }

        return false;
    }

    public function file(
        string $path,
    ): UploadedFileInterface {
        $node = $this->resolve($path);

        if ($node === null) {
            throw HttpException::fromInternalServerError();
        }

        if (!$this->isLeaf($node)) {
            throw UploadedFileException::fromSingleFileExpectedAtPath(
                path: $path,
            );
        }

        /** @var phpUploadedFileArray $node */
        return $this->build($node);
    }

    public function arrayOfFile(
        string $path,
    ): array {
        $node = $this->resolve($path);

        if ($node === null) {
            throw HttpException::fromInternalServerError();
        }

        if ($this->isLeaf($node)) {
            throw UploadedFileException::fromArrayExpectedAtPath(
                path: $path,
            );
        }

        if (!\is_array($node) || !\array_is_list($node)) {
            throw UploadedFileException::fromListExpectedAtPath(
                path: $path,
            );
        }

        $result = [];

        foreach ($node as $entry) {
            if (!$this->isLeaf($entry)) {
                throw UploadedFileException::fromLeavesExpectedAtPath(
                    path: $path,
                );
            }

            /** @var phpUploadedFileArray $entry */
            $result[] = $this->build($entry);
        }

        return $result;
    }

    /**
     * @param phpUploadedFileArray $file
     */
    private function build(
        array $file,
    ): UploadedFileInterface {
        match ($file['error']) {
            \UPLOAD_ERR_INI_SIZE, \UPLOAD_ERR_FORM_SIZE => throw UploadedFileException::fromSizeMismatch(),
            \UPLOAD_ERR_PARTIAL, \UPLOAD_ERR_NO_FILE => throw UploadedFileException::fromIncompleteFile(),
            \UPLOAD_ERR_NO_TMP_DIR, \UPLOAD_ERR_CANT_WRITE => throw UploadedFileException::fromSaveFile(),
            \UPLOAD_ERR_EXTENSION => throw UploadedFileException::fromExtensionError(),
            default => null,
        };

        return new UploadedFile(
            name: $file['name'],
            browserType: $file['type'],
            size: $file['size'],
            temporaryPath: $file['tmp_name'],
            browserPath: $file['full_path'],
        );
    }
}
