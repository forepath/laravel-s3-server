<?php

declare(strict_types=1);

namespace LaravelS3Server\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use LaravelS3Server\Contracts\AuthenticationProviderInterface;

/**
 * S3 signature middleware.
 *
 * This middleware is used to verify the S3 signature
 *
 * @author Marcel Menk <marcel.menk@ipvx.io>
 */
class S3SignatureMiddleware
{
    /**
     * @var AuthenticationProviderInterface
     */
    protected AuthenticationProviderInterface $authProvider;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $authDriverClass = config('s3server.auth_driver');

        $this->authProvider = app($authDriverClass);
    }

    /**
     * Handle the S3 request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (! $this->authProvider->authenticate($request)) {
            return response('Unauthorized', Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
