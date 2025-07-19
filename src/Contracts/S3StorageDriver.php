<?php

declare(strict_types=1);

namespace LaravelS3Server\Contracts;

/**
 * S3 storage driver contract.
 *
 * This contract is used to define the methods that a storage driver must implement
 *
 * @author Marcel Menk <marcel.menk@ipvx.io>
 */
interface S3StorageDriver
{
    /**
     * Put a file into the storage.
     *
     * @param string $path
     * @param string $content
     */
    public function put(string $path, string $content): void;

    /**
     * Get a file from the storage.
     *
     * @param string $path
     *
     * @return string|null
     */
    public function get(string $path): ?string;

    /**
     * Delete a file from the storage.
     *
     * @param string $path
     */
    public function delete(string $path): void;

    /**
     * List all files in a directory.
     *
     * @param string $prefix
     *
     * @return array
     */
    public function list(string $prefix): array;
}
