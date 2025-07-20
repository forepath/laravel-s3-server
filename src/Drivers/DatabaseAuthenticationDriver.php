<?php

declare(strict_types=1);

namespace LaravelS3Server\Drivers;

use DateTime;
use Illuminate\Http\Request;
use LaravelS3Server\Contracts\AuthenticationDriver;
use LaravelS3Server\Models\S3AccessCredential;

/**
 * Database authentication driver.
 *
 * This driver is used to authenticate S3 requests using database credentials
 *
 * @author Marcel Menk <marcel.menk@ipvx.io>
 */
class DatabaseAuthenticationDriver implements AuthenticationDriver
{
    /**
     * Authenticate the incoming HTTP request.
     *
     * @param Request $request
     *
     * @return bool True if authenticated, false otherwise
     */
    public function authenticate(Request $request): bool
    {
        // Check if Authorization header is present
        $authorization = $request->header('Authorization');

        if (!$authorization || !str_starts_with($authorization, 'AWS4-HMAC-SHA256')) {
            return false;
        }

        // Extract access key ID from Authorization header
        $accessKeyId = $this->extractAccessKeyId($authorization);

        if (!$accessKeyId) {
            return false;
        }

        // Get the credential from database
        $credential = $this->getCredentialByAccessKeyId($accessKeyId);

        if (!$credential) {
            return false;
        }

        // Check bucket access permission
        if (!$this->checkBucketAccess($request, $credential)) {
            return false;
        }

        // Verify the signature
        return $this->verifySignature($request, $credential->secret_access_key, $authorization);
    }

    /**
     * Get the credential by access key id.
     *
     * @param string $accessKeyId
     *
     * @return S3AccessCredential|null
     */
    public function getCredentialByAccessKeyId(string $accessKeyId): ?S3AccessCredential
    {
        return S3AccessCredential::where('access_key_id', $accessKeyId)->first();
    }

    /**
     * Get the secret key by access key id.
     *
     * @param string $accessKeyId
     *
     * @return string|null
     */
    public function getSecretKeyByAccessKeyId(string $accessKeyId): ?string
    {
        $credential = $this->getCredentialByAccessKeyId($accessKeyId);

        return $credential ? $credential->secret_access_key : null;
    }

    /**
     * Check if the credential has access to the requested bucket.
     *
     * @param Request            $request
     * @param S3AccessCredential $credential
     *
     * @return bool
     */
    private function checkBucketAccess(Request $request, S3AccessCredential $credential): bool
    {
        // Extract bucket from the request path
        $path = $request->getPathInfo();

        // The path format is /s3/{bucket}/{key?}
        $pathParts = explode('/', trim($path, '/'));

        if (count($pathParts) < 2 || $pathParts[0] !== 's3') {
            return false;
        }

        $requestedBucket = $pathParts[1];

        // If credential has no bucket restriction (bucket is null), allow access to any bucket
        if ($credential->bucket === null) {
            return true;
        }

        // Check if the requested bucket matches the credential's allowed bucket
        return $credential->bucket === $requestedBucket;
    }

    /**
     * Extract access key ID from Authorization header.
     *
     * @param string $authorization
     *
     * @return string|null
     */
    private function extractAccessKeyId(string $authorization): ?string
    {
        // Format: AWS4-HMAC-SHA256 Credential=access_key_id/date/region/service/aws4_request, ...
        if (preg_match('/Credential=([^\/]+)/', $authorization, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Verify the S3 signature.
     *
     * @param Request $request
     * @param string  $secretKey
     * @param string  $authorization
     *
     * @return bool
     */
    private function verifySignature(Request $request, string $secretKey, string $authorization): bool
    {
        // Extract signature components
        $signatureComponents = $this->parseAuthorizationHeader($authorization);

        if (!$signatureComponents) {
            return false;
        }

        // Get the date from headers
        $date = $request->header('X-Amz-Date') ?: $request->header('Date');

        if (!$date) {
            return false;
        }

        // Parse the date
        $dateTime = DateTime::createFromFormat('Ymd\THis\Z', $date);

        if (!$dateTime) {
            return false;
        }

        $dateStamp = $dateTime->format('Ymd');
        $region    = $signatureComponents['region'] ?? 'us-east-1';
        $service   = $signatureComponents['service'] ?? 's3';

        // Build the canonical request
        $canonicalRequest = $this->buildCanonicalRequest($request, $signatureComponents);

        // Build the string to sign
        $stringToSign = $this->buildStringToSign($dateTime, $region, $service, $canonicalRequest);

        // Calculate the signature
        $signature = $this->calculateSignature($secretKey, $dateStamp, $region, $service, $stringToSign);

        // Compare signatures
        return hash_equals($signatureComponents['signature'], $signature);
    }

    /**
     * Parse the Authorization header.
     *
     * @param string $authorization
     *
     * @return array|null
     */
    private function parseAuthorizationHeader(string $authorization): ?array
    {
        // Format: AWS4-HMAC-SHA256 Credential=access_key_id/date/region/service/aws4_request, SignedHeaders=..., Signature=...
        if (!preg_match('/Credential=([^,]+), SignedHeaders=([^,]+), Signature=([^,\s]+)/', $authorization, $matches)) {
            return null;
        }

        $credential    = $matches[1];
        $signedHeaders = $matches[2];
        $signature     = $matches[3];

        // Parse credential
        $credentialParts = explode('/', $credential);

        if (count($credentialParts) !== 5) {
            return null;
        }

        return [
            'access_key_id'  => $credentialParts[0],
            'date'           => $credentialParts[1],
            'region'         => $credentialParts[2],
            'service'        => $credentialParts[3],
            'signed_headers' => $signedHeaders,
            'signature'      => $signature,
        ];
    }

    /**
     * Build the canonical request.
     *
     * @param Request $request
     * @param array   $signatureComponents
     *
     * @return string
     */
    private function buildCanonicalRequest(Request $request, array $signatureComponents): string
    {
        $method      = $request->method();
        $uri         = $request->getRequestUri();
        $queryString = $request->getQueryString() ?: '';

        // Sort query parameters
        if ($queryString) {
            $queryParams = [];
            parse_str($queryString, $queryParams);
            ksort($queryParams);
            $queryString = http_build_query($queryParams);
        }

        // Get the signed headers from the authorization header
        $signedHeadersList = explode(';', $signatureComponents['signed_headers']);

        // Build canonical headers - only include signed headers
        $canonicalHeaders = '';
        $signedHeaders    = '';

        foreach ($signedHeadersList as $headerName) {
            $headerValue = $request->header($headerName);

            if ($headerValue !== null) {
                $canonicalHeaders .= strtolower($headerName) . ':' . trim($headerValue) . "\n";
                $signedHeaders .= strtolower($headerName) . ';';
            }
        }
        $signedHeaders = rtrim($signedHeaders, ';');

        // Get payload hash
        $payloadHash = hash('sha256', $request->getContent() ?: '');

        return $method . "\n" .
               $uri . "\n" .
               $queryString . "\n" .
               $canonicalHeaders . "\n" .
               $signedHeaders . "\n" .
               $payloadHash;
    }

    /**
     * Build the string to sign.
     *
     * @param DateTime $dateTime
     * @param string   $region
     * @param string   $service
     * @param string   $canonicalRequest
     *
     * @return string
     */
    private function buildStringToSign(DateTime $dateTime, string $region, string $service, string $canonicalRequest): string
    {
        $scope                = $dateTime->format('Ymd') . '/' . $region . '/' . $service . '/aws4_request';
        $canonicalRequestHash = hash('sha256', $canonicalRequest);

        return 'AWS4-HMAC-SHA256' . "\n" .
               $dateTime->format('Ymd\THis\Z') . "\n" .
               $scope . "\n" .
               $canonicalRequestHash;
    }

    /**
     * Calculate the signature.
     *
     * @param string $secretKey
     * @param string $dateStamp
     * @param string $region
     * @param string $service
     * @param string $stringToSign
     *
     * @return string
     */
    private function calculateSignature(string $secretKey, string $dateStamp, string $region, string $service, string $stringToSign): string
    {
        $kDate    = hash_hmac('sha256', $dateStamp, 'AWS4' . $secretKey, true);
        $kRegion  = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

        return hash_hmac('sha256', $stringToSign, $kSigning);
    }
}
