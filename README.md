# Laravel S3 Server

[![Latest Version on Packagist](https://img.shields.io/packagist/v/forepath/laravel-s3-server.svg?style=flat-square)](https://packagist.org/packages/forepath/laravel-s3-server)
[![Tests](https://img.shields.io/github/actions/workflow/status/forepath/laravel-s3-server/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/forepath/laravel-s3-server/actions)
[![Total Downloads](https://img.shields.io/packagist/dt/forepath/laravel-s3-server.svg?style=flat-square)](https://packagist.org/packages/forepath/laravel-s3-server)
[![License](https://img.shields.io/github/license/forepath/laravel-s3-server.svg?style=flat-square)](https://github.com/forepath/laravel-s3-server/blob/main/LICENSE)

A lightweight, Laravel-compatible Amazon S3 protocol server that allows you to run your own S3-compatible storage service within your Laravel application. Perfect for development, testing, or self-hosted storage solutions.

## Features

- 🔐 **Full S3 Protocol Support** - Compatible with AWS S3 API v4
- 🔐 **Secure Authentication** - AWS Signature Version 4 (AWS4-HMAC-SHA256) authentication
- 💾 **Database-Backed Credentials** - Store and manage S3 credentials in your database
- 🔒 **Encrypted Storage** - Secret keys are automatically encrypted using Laravel's encryption
- 🔒 **Flexible Storage Drivers** - File-based storage with extensible driver system
- 🛡️ **Laravel Integration** - Seamless integration with Laravel's service container
- ⚡ **Lightweight** - Minimal overhead, maximum performance
- 🔒 **Production Ready** - Built with security and reliability in mind

## Requirements

- PHP 8.1 or higher
- Laravel 10, 11, or 12
- Database (MySQL, PostgreSQL, SQLite, etc.)

## Installation

You can install the package via Composer:

```bash
composer require forepath/laravel-s3-server
```

## Quick Start

### 1. Publish Configuration

Publish the configuration file to customize the S3 server settings:

```bash
php artisan vendor:publish --provider="LaravelS3Server\S3ServiceProvider" --tag="s3server-config"
```

### 2. Run Migrations

Run the migrations to create the necessary database tables:

```bash
php artisan migrate
```

### 3. Create S3 Credentials

Add S3 credentials to your database:

```php
use LaravelS3Server\Models\S3AccessCredential;

S3AccessCredential::create([
    'access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
    'secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
    'description' => 'Development credentials',
]);
```

### 4. Start Using S3

Your S3 server is now available at `/s3/{bucket}/{key}` and supports all standard S3 operations!

## Configuration

The configuration file is located at `config/s3server.php`:

```php
return [
    // Enable/disable authentication
    'auth' => true,
    
    // Authentication driver class
    'auth_driver' => LaravelS3Server\Drivers\DatabaseAuthenticationDriver::class,
    
    // Storage driver class
    'storage_driver' => LaravelS3Server\Drivers\FileStorageDriver::class,
];
```

### Authentication Settings

- `auth`: Set to `false` to disable authentication (not recommended for production)
- `auth_driver`: The authentication driver class to use

### Storage Settings

- `storage_driver`: The storage driver class to use for file operations

## Usage

### S3 API Endpoints

The package automatically registers the following S3-compatible endpoints:

| Method | Endpoint | Description |
|--------|----------|-------------|
| `PUT` | `/s3/{bucket}/{key}` | Upload an object |
| `GET` | `/s3/{bucket}/{key}` | Download an object |
| `GET` | `/s3/{bucket}` | List bucket contents |
| `DELETE` | `/s3/{bucket}/{key}` | Delete an object |

### Using with AWS CLI

Configure AWS CLI to use your local S3 server:

```bash
aws configure set aws_access_key_id AKIAIOSFODNN7EXAMPLE
aws configure set aws_secret_access_key wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
aws configure set region us-east-1
aws configure set endpoint_url http://localhost:8000/s3
```

### Using with AWS SDK

```php
use Aws\S3\S3Client;

$s3Client = new S3Client([
    'version' => 'latest',
    'region'  => 'us-east-1',
    'endpoint' => 'http://localhost:8000/s3',
    'use_path_style_endpoint' => true,
    'credentials' => [
        'key'    => 'AKIAIOSFODNN7EXAMPLE',
        'secret' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
    ],
]);

// Upload a file
$s3Client->putObject([
    'Bucket' => 'my-bucket',
    'Key'    => 'example.txt',
    'Body'   => 'Hello, World!',
]);

// Download a file
$result = $s3Client->getObject([
    'Bucket' => 'my-bucket',
    'Key'    => 'example.txt',
]);

echo $result['Body'];
```

### Managing Credentials

#### Creating Credentials

```php
use LaravelS3Server\Models\S3AccessCredential;

// Create a new credential
$credential = S3AccessCredential::create([
    'access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
    'secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
    'description' => 'Development credentials',
]);
```

#### Listing Credentials

```php
// Get all credentials
$credentials = S3AccessCredential::all();

// Find by access key ID
$credential = S3AccessCredential::where('access_key_id', 'AKIAIOSFODNN7EXAMPLE')->first();
```

#### Updating Credentials

```php
$credential = S3AccessCredential::where('access_key_id', 'AKIAIOSFODNN7EXAMPLE')->first();
$credential->update([
    'description' => 'Updated description',
]);
```

#### Deleting Credentials

```php
$credential = S3AccessCredential::where('access_key_id', 'AKIAIOSFODNN7EXAMPLE')->first();
$credential->delete();
```

## Architecture

### Authentication Drivers

The package uses a driver-based authentication system. The default `DatabaseAuthenticationDriver` authenticates requests using credentials stored in the database.

#### Custom Authentication Driver

Create a custom authentication driver by implementing the `AuthenticationProviderInterface`:

```php
<?php

namespace App\Services;

use Illuminate\Http\Request;
use LaravelS3Server\Contracts\AuthenticationProviderInterface;

class CustomAuthDriver implements AuthenticationProviderInterface
{
    public function authenticate(Request $request): bool
    {
        // Your custom authentication logic
        return true;
    }

    public function getSecretKeyByAccessKeyId(string $accessKeyId): ?string
    {
        // Return the secret key for the given access key ID
        return 'your-secret-key';
    }
}
```

Then update your configuration:

```php
'auth_driver' => App\Services\CustomAuthDriver::class,
```

### Storage Drivers

The package uses a driver-based storage system. The default `FileStorageDriver` stores files using Laravel's Storage facade.

#### Custom Storage Driver

Create a custom storage driver by implementing the `S3StorageDriver` interface:

```php
<?php

namespace App\Services;

use LaravelS3Server\Contracts\S3StorageDriver;

class CustomStorageDriver implements S3StorageDriver
{
    public function put(string $path, string $content): void
    {
        // Store the file
    }

    public function get(string $path): ?string
    {
        // Retrieve the file content
        return null;
    }

    public function delete(string $path): void
    {
        // Delete the file
    }

    public function list(string $prefix): array
    {
        // List files in the prefix
        return [];
    }
}
```

Then update your configuration:

```php
'storage_driver' => App\Services\CustomStorageDriver::class,
```

## Security

### Credential Encryption

All secret access keys are automatically encrypted using Laravel's built-in encryption system. The encryption uses your application's `APP_KEY` and AES-256-CBC encryption.

### Authentication

The package implements AWS Signature Version 4 (AWS4-HMAC-SHA256) authentication, which is the same authentication method used by AWS S3. This ensures compatibility with all S3 clients and SDKs.

### Best Practices

1. **Use HTTPS in Production**: Always use HTTPS when deploying to production
2. **Rotate Credentials**: Regularly rotate your S3 credentials
3. **Monitor Access**: Implement logging to monitor S3 access
4. **Backup APP_KEY**: Ensure your `APP_KEY` is backed up securely
5. **Limit Permissions**: Use the principle of least privilege

## Testing

### Manual Testing

You can test the S3 server using the AWS CLI:

```bash
# List buckets (will show bucket-like directories)
aws s3 ls s3:// --endpoint-url http://localhost:8000/s3

# Create a bucket (upload a file to create the bucket)
aws s3 cp test.txt s3://my-bucket/test.txt --endpoint-url http://localhost:8000/s3

# List bucket contents
aws s3 ls s3://my-bucket/ --endpoint-url http://localhost:8000/s3

# Download a file
aws s3 cp s3://my-bucket/test.txt downloaded.txt --endpoint-url http://localhost:8000/s3

# Delete a file
aws s3 rm s3://my-bucket/test.txt --endpoint-url http://localhost:8000/s3
```

### Programmatic Testing

```php
use LaravelS3Server\Models\S3AccessCredential;

// Create test credentials
S3AccessCredential::create([
    'access_key_id' => 'test-key',
    'secret_access_key' => 'test-secret',
    'description' => 'Test credentials',
]);

// Test with AWS SDK
$s3Client = new S3Client([
    'version' => 'latest',
    'region'  => 'us-east-1',
    'endpoint' => 'http://localhost:8000/s3',
    'use_path_style_endpoint' => true,
    'credentials' => [
        'key'    => 'test-key',
        'secret' => 'test-secret',
    ],
]);

// Test upload
$s3Client->putObject([
    'Bucket' => 'test-bucket',
    'Key'    => 'test.txt',
    'Body'   => 'Test content',
]);
```

## Troubleshooting

### Common Issues

#### 1. Authentication Failures

**Problem**: Getting 401 Unauthorized errors

**Solutions**:
- Verify credentials exist in the database
- Check that the access key ID matches exactly
- Ensure the request includes proper AWS Signature Version 4 headers
- Verify your `APP_KEY` is set correctly

#### 2. File Storage Issues

**Problem**: Files not being stored or retrieved

**Solutions**:
- Check Laravel's storage configuration
- Verify storage directory permissions
- Ensure the storage driver is properly configured

#### 3. Configuration Issues

**Problem**: Configuration not being loaded

**Solutions**:
- Publish the configuration file: `php artisan vendor:publish --provider="LaravelS3Server\S3ServiceProvider" --tag="s3server-config"`
- Clear configuration cache: `php artisan config:clear`
- Verify the service provider is registered

#### 4. Migration Issues

**Problem**: Database tables not created

**Solutions**:
- Run migrations: `php artisan migrate`
- Check database connection
- Verify migration files are present

### Debug Mode

Enable debug mode to get more detailed error information:

```php
// In config/s3server.php
'debug' => true,
```

## Contributing

We welcome contributions! Please see our [Contributing Guide](https://github.com/forepath/laravel-s3-server/blob/main/CONTRIBUTING.md) for details.

### Development Setup

1. Fork the repository
2. Clone your fork
3. Install dependencies: `composer install`
4. Run tests: `composer test`
5. Make your changes
6. Submit a pull request

### Code Style

This package follows Laravel's coding standards. We use [Laravel Pint](https://laravel.com/docs/pint) for code formatting:

```bash
./vendor/bin/pint
```

## Changelog

Please see [CHANGELOG.md](https://github.com/forepath/laravel-s3-server/blob/main/CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Marcel Menk](https://github.com/marcelmenk) - Author
- [ForePath](https://forepath.io/open-source-projekte/) - Company
- [All Contributors](https://github.com/forepath/laravel-s3-server/graphs/contributors)

## License

The MIT License (MIT). Please see [License File](https://github.com/forepath/laravel-s3-server/blob/main/LICENSE) for more information.

## Support

- **Documentation**: [GitHub Wiki](https://github.com/forepath/laravel-s3-server/wiki)
- **Issues**: [GitHub Issues](https://github.com/forepath/laravel-s3-server/issues)
- **Discussions**: [GitHub Discussions](https://github.com/forepath/laravel-s3-server/discussions)

---

**Built with ❤️ for the Laravel community**