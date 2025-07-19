<?php

declare(strict_types=1);

namespace LaravelS3Server\Contracts;

use Illuminate\Http\Request;

/**
 * Authentication driver contract.
 *
 * This contract is used to define the methods that an authentication driver must implement
 *
 * @author Marcel Menk <marcel.menk@ipvx.io>
 */
interface AuthenticationProviderInterface
{
    /**
     * Authenticate the incoming HTTP request.
     *
     * @param Request $request
     *
     * @return bool True if authenticated, false otherwise
     */
    public function authenticate(Request $request): bool;

    /**
     * Optionally, get the secret key by access key id (could be used internally).
     *
     * @param string $accessKeyId
     *
     * @return string|null
     */
    public function getSecretKeyByAccessKeyId(string $accessKeyId): ?string;
}
