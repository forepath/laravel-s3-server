<?php

declare(strict_types=1);

/**
 * S3 server configuration.
 *
 * This configuration is used to configure the S3 server
 *
 * @author Marcel Menk <marcel.menk@ipvx.io>
 */
return [
    'auth'           => env('S3SERVER_AUTH', true),
    'auth_driver'    => env('S3SERVER_AUTH_DRIVER', LaravelS3Server\Drivers\DatabaseAuthenticationDriver::class),
    'storage_driver' => env('S3SERVER_STORAGE_DRIVER', LaravelS3Server\Drivers\FileStorageDriver::class),
    'storage_path'   => env('S3SERVER_STORAGE_PATH', 's3/'),
];
