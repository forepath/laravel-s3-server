<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use LaravelS3Server\Services\S3RequestHandler;

/**
 * S3 routes.
 *
 * This routes are used to handle the S3 requests
 */
Route::any('/s3/{bucket}/{key?}', function ($bucket, $key = null) {
    $handler = new S3RequestHandler(fn () => app(config('s3server.storage_driver')));

    return $handler->handle(request(), $bucket, $key);
})->where('key', '.*')->middleware(config('s3server.auth') ? ['s3.auth'] : []);
