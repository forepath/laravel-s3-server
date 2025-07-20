<?php

declare(strict_types=1);

namespace LaravelS3Server\Drivers;

use Illuminate\Support\Facades\Storage;
use LaravelS3Server\Contracts\S3StorageDriver;

/**
 * File storage driver.
 *
 * This driver is used to store files in the filesystem
 *
 * @author Marcel Menk <marcel.menk@ipvx.io>
 */
class FileStorageDriver implements S3StorageDriver
{
    /**
     * Get the content of a file.
     *
     * @param string $path
     *
     * @return string|null
     */
    public function get(string $path): ?string
    {
        return Storage::get($path);
    }

    /**
     * Put a file into the storage.
     *
     * @param string $path
     * @param string $content
     */
    public function put(string $path, string $content): void
    {
        // Ensure the directory structure exists before writing the file
        $this->ensureDirectoryExists($path);

        Storage::put($path, $content);
    }

    /**
     * Delete a file from the storage.
     *
     * @param string $path
     */
    public function delete(string $path): void
    {
        Storage::deleteDirectory($path);
        Storage::delete($path);
    }

    /**
     * List all files in a directory.
     *
     * @param string $prefix
     *
     * @return array
     */
    public function list(string $prefix): array
    {
        return Storage::files($prefix);
    }

    /**
     * Ensure that the directory structure exists for the given path.
     *
     * @param string $path
     */
    private function ensureDirectoryExists(string $path): void
    {
        $directory = dirname($path);

        // Skip if it's the root directory or current directory
        if ($directory === '.' || $directory === '/' || $directory === '') {
            return;
        }

        // Create the directory if it doesn't exist
        if (!Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }
    }
}
