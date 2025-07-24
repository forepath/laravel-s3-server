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
        return Storage::get(config('s3server.storage_path') . $path);
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

        Storage::put(config('s3server.storage_path') . $path, $content);
    }

    /**
     * Delete a file from the storage.
     *
     * @param string $path
     */
    public function delete(string $path): void
    {
        Storage::deleteDirectory(config('s3server.storage_path') . $path);
        Storage::delete(config('s3server.storage_path') . $path);
    }

    /**
     * List all files and prefixes in a directory.
     *
     * @param string $prefix
     * @param array  $options
     *
     * @return array{files: array<string>, prefixes: array<string>, isTruncated: bool, nextMarker: ?string}
     */
    public function list(string $prefix, array $options = []): array
    {
        $maxKeys     = $options['maxKeys'] ?? 1000;
        $marker      = $options['marker'] ?? null;
        $recursive   = $options['recursive'] ?? false;
        $queryPrefix = $options['prefix'] ?? '';

        // Build the full prefix to search under
        $searchPrefix = $prefix;

        if (!empty($queryPrefix)) {
            $searchPrefix = rtrim($prefix, '/') . '/' . ltrim($queryPrefix, $prefix . '/');
        }

        // Get all files under the search prefix
        $allFiles = Storage::allFiles(config('s3server.storage_path') . $searchPrefix);

        if ($recursive) {
            // Return all files recursively, filtered by the query prefix
            $files = array_filter($allFiles, function ($file) use ($searchPrefix) {
                return str_starts_with($file, $searchPrefix);
            });
            $prefixes = [];
        } else {
            // Filter to only immediate files and directories
            $files       = [];
            $directories = [];

            foreach ($allFiles as $file) {
                // Get the relative path from the search prefix
                $relativePath = substr($file, strlen($searchPrefix));
                $relativePath = ltrim($relativePath, '/');

                // If there's no slash, it's an immediate file
                if (strpos($relativePath, '/') === false) {
                    $files[] = $file;
                } else {
                    // It's in a subdirectory, extract the directory name
                    $dirName = explode('/', $relativePath)[0];

                    // Handle root bucket case - don't add extra slashes
                    if (empty($searchPrefix)) {
                        $dirPath = $dirName;
                    } else {
                        $dirPath = $searchPrefix . '/' . $dirName;
                    }

                    if (!in_array($dirPath, $directories)) {
                        $directories[] = $dirPath;
                    }
                }
            }

            // Convert directories to S3-style prefixes
            $prefixes = array_map(function ($directory) {
                return $directory . '/';
            }, $directories);
        }

        // Apply marker filtering to both files and prefixes
        if ($marker) {
            $files    = array_filter($files, fn ($file) => $file > $marker);
            $prefixes = array_filter($prefixes, fn ($prefix) => $prefix > $marker);
        }

        // Combine files and prefixes for pagination
        $allItems = array_merge($files, $prefixes);
        sort($allItems);

        // Apply max-keys limit
        $isTruncated = count($allItems) > $maxKeys;
        $allItems    = array_slice($allItems, 0, $maxKeys);

        // Separate files and prefixes from the paginated results
        $resultFiles    = [];
        $resultPrefixes = [];

        foreach ($allItems as $item) {
            if (str_ends_with($item, '/')) {
                $resultPrefixes[] = $item;
            } else {
                $resultFiles[] = $item;
            }
        }

        return [
            'files'       => $resultFiles,
            'prefixes'    => $resultPrefixes,
            'isTruncated' => $isTruncated,
            'nextMarker'  => $isTruncated ? end($allItems) : null,
        ];
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
        if (!Storage::exists(config('s3server.storage_path') . $directory)) {
            Storage::makeDirectory(config('s3server.storage_path') . $directory);
        }
    }
}
