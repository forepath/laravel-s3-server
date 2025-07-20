<?php

declare(strict_types=1);

namespace LaravelS3Server\Services;

use Illuminate\Http\Request;
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
            'PUT'    => $this->putObject($bucket, $key, $request),
            'GET'    => $key ? $this->getObject($bucket, $key) : $this->listBucket($bucket),
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
     * @param string $bucket
     *
     * @return Response
     */
    protected function listBucket(string $bucket): Response
    {
        $files = $this->storageDriver->list($bucket);
        $xml   = view('s3server::s3.list', ['bucket' => $bucket, 'files' => $files])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
