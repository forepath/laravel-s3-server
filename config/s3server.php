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
    'auth'           => true,
    'auth_driver'    => LaravelS3Server\Drivers\DatabaseAuthenticationDriver::class,
    'storage_driver' => LaravelS3Server\Drivers\FileStorageDriver::class,
];
