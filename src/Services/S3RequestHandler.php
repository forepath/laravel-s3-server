<?php

declare(strict_types=1);

namespace LaravelS3Server\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use LaravelS3Server\Contracts\S3StorageDriver;
use Symfony\Component\HttpFoundation\Response;

/**
 * S3 request handler.
 *
 * This class is used to handle the S3 requests
 *
 * @author Marcel Menk <marcel.menk@ipvx.io>
 */
class S3RequestHandler
{
    /**
     * @var S3StorageDriver
     */
    protected $storageDriver;

    /**
     * Constructor.
     *
     * @param callable $driverResolver
     */
    public function __construct(callable $driverResolver)
    {
        $this->storageDriver = $driverResolver(); // expects closure that returns a storage implementation
    }

    /**
     * Handle the S3 request.
     *
     * @param Request     $request
     * @param string      $bucket
     * @param string|null $key
     *
     * @return Response
     */
    public function handle(Request $request, string $bucket, ?string $key = null): Response
    {
        $method = $request->method();

        return match ($method) {
            'PUT' => $this->putObject($bucket, $key, $request),
            'GET' => !Storage::directoryExists(config('s3server.storage_path') . $bucket . '/' . $key) && $key ?
                $this->getObject($bucket, $key) :
                $this->listBucket($bucket, $key),
            'HEAD'   => $this->headObject($bucket, $key),
            'DELETE' => $this->deleteObject($bucket, $key),
            default  => response('Not Implemented', 501),
        };
    }

    /**
     * Put an object into the storage.
     *
     * @param string  $bucket
     * @param string  $key
     * @param Request $request
     *
     * @return Response
     */
    protected function putObject(string $bucket, string $key, Request $request): Response
    {
        $path = "$bucket/$key";

        $this->storageDriver->put($path, $request->getContent());

        return response('', 200);
    }

    /**
     * Get an object from the storage.
     *
     * @param string $bucket
     * @param string $key
     *
     * @return Response
     */
    protected function getObject(string $bucket, string $key): Response
    {
        $path    = "$bucket/$key";
        $content = $this->storageDriver->get($path);

        if ($content === null) {
            return response('Not Found', 404);
        }

        return response($content, 200)
            ->header('Content-Type', 'application/octet-stream')
            ->header('Content-Length', strlen($content));
    }

    /**
     * Head an object from the storage (check if exists and get metadata).
     *
     * @param string $bucket
     * @param string $key
     *
     * @return Response
     */
    protected function headObject(string $bucket, string $key): Response
    {
        $path = "$bucket/$key";

        // Check if the object exists by trying to get it
        $content = $this->storageDriver->get($path);

        if ($content === null) {
            return response('Not Found', 404);
        }

        // Return empty response with 200 status for HEAD requests
        // The content is not included in HEAD responses, only headers
        return response('', 200)
            ->header('Content-Type', 'application/octet-stream')
            ->header('Content-Length', strlen($content));
    }

    /**
     * Delete an object from the storage.
     *
     * @param string $bucket
     * @param string $key
     *
     * @return Response
     */
    protected function deleteObject(string $bucket, string $key): Response
    {
        $path = "$bucket/$key";

        $this->storageDriver->delete($path);

        return response('', 204);
    }

    /**
     * List a bucket.
     *
     * @param string  $bucket
     * @param ?string $key
     *
     * @return Response
     */
    protected function listBucket(string $bucket, ?string $key = null): Response
    {
        $path = $key ? "$bucket/$key" : $bucket;

        // Get S3 query parameters
        $request   = request();
        $delimiter = $request->query('delimiter');
        $prefix    = $request->query('prefix', $key ? "$bucket/$key" : $bucket);
        $maxKeys   = (int) $request->query('max-keys', 1000);
        $marker    = $request->query('marker');

        // Determine if listing should be recursive based on delimiter
        $recursive = $delimiter === null || $delimiter === '';

        $listing = $this->storageDriver->list($path, [
            'delimiter' => $delimiter,
            'prefix'    => $prefix,
            'maxKeys'   => $maxKeys,
            'marker'    => $marker,
            'recursive' => $recursive,
        ]);

        // Convert full storage paths to relative S3 keys for display
        $files = array_map(function ($file) use ($bucket) {
            // Remove the bucket prefix to get relative key
            return str_replace("$bucket/", '', $file);
        }, $listing['files']);

        $prefixes = array_map(function ($prefixPath) use ($bucket) {
            // Remove the bucket prefix to get relative prefix
            return str_replace("$bucket/", '', $prefixPath);
        }, $listing['prefixes']);

        $xml = view('s3server::s3.list', [
            'bucket'      => $bucket,
            'files'       => $files,
            'prefixes'    => $prefixes,
            'fullPaths'   => $listing['files'], // Keep full paths for storage operations
            'delimiter'   => $delimiter,
            'prefix'      => $prefix,
            'maxKeys'     => $maxKeys,
            'marker'      => $marker,
            'isTruncated' => $listing['isTruncated'] ?? false,
            'nextMarker'  => $listing['nextMarker'] ?? null,
        ])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
