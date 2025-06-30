# Fireboost PHP SDK

Fireboost PHP SDK is a PHP library for interacting with the Fireboost API. This SDK provides functionalities to manage cache operations, including saving, reading, and deleting cached data with automatic authentication handling.

## Installation

You can install the Fireboost PHP SDK via Composer:

```bash
composer require fireboostio/fireboost-php-sdk
```

## Usage

Below are examples demonstrating how to use the SDK:

### Basic Usage

```php
<?php
use FireboostIO\SDK\CacheManager;
use FireboostIO\SDK\Adapter\SessionAdapter;

// Create a CacheManager instance with default SessionAdapter
$apiKey = 'your-api-key'; // Or set FIREBOOST_API_KEY environment variable
$cacheManager = new CacheManager(new SessionAdapter(), $apiKey);

// Save data to cache
$cacheKey = 'example/key';
$content = ['name' => 'Example', 'value' => 123];
$isPublic = true; // Set to true for publicly accessible cache
$response = $cacheManager->saveCache($cacheKey, $content, $isPublic);

// Read data from cache
$data = $cacheManager->readCache($cacheKey);

// Read public data (no authentication required)
$publicData = $cacheManager->readPublicCache($cacheKey);

// Delete data from cache
$cacheManager->deleteCache($cacheKey);

// Delete all data from cache
$cacheManager->deleteAllCache();

// Get cache usage statistics
$stats = $cacheManager->getStatistics();
```

### Using Different Storage Adapters

The SDK supports multiple storage adapters for JWT tokens:

#### Session Adapter (Default)

```php
use FireboostIO\SDK\Adapter\SessionAdapter;

$adapter = new SessionAdapter();
// Or with custom session keys:
$adapter = new SessionAdapter('custom_token_key', 'custom_login_attempts_key');
```

#### Redis Adapter

```php
use FireboostIO\SDK\Adapter\RedisAdapter;

// With default Redis connection
$adapter = new RedisAdapter();

// Or with custom Redis connection
$redis = new \Redis();
$redis->connect('redis-server.example.com', 6379);
$adapter = new RedisAdapter($redis, 'custom:token:key:', 'custom:login:attempts:', 3600);
```

#### Database Adapter

```php
use FireboostIO\SDK\Adapter\DatabaseAdapter;

$pdo = new PDO('mysql:host=localhost;dbname=myapp', 'username', 'password');
$adapter = new DatabaseAdapter($pdo, 'custom_tokens_table', 'user_identifier');
```

#### File Adapter

```php
use FireboostIO\SDK\Adapter\FileAdapter;

// With default system temp directory
$adapter = new FileAdapter();

// Or with custom storage directory
$adapter = new FileAdapter('/path/to/storage', 'token_filename', 'login_attempts_filename');
```

### Creating Your Own Custom Adapter

You can create your own custom adapter by implementing the `TokenStorageAdapterInterface`. This allows you to store JWT tokens and login attempt counters in any storage system of your choice.

```php
<?php

namespace YourNamespace;

use FireboostIO\SDK\Adapter\TokenStorageAdapterInterface;

class CustomAdapter implements TokenStorageAdapterInterface
{
    // Your storage mechanism (e.g., a custom database connection, API, etc.)
    private $storage;

    public function __construct($storage)
    {
        $this->storage = $storage;
    }

    /**
     * Store a JWT token
     */
    public function storeToken(string $token): bool
    {
        // Implement token storage logic
        // Return true if successful, false otherwise
        return true;
    }

    /**
     * Retrieve the stored JWT token
     */
    public function getToken(): ?string
    {
        // Implement token retrieval logic
        // Return the token or null if not found
        return $storedToken ?? null;
    }

    /**
     * Clear the stored JWT token
     */
    public function clearToken(): bool
    {
        // Implement token clearing logic
        // Return true if successful, false otherwise
        return true;
    }

    /**
     * Check if a token is stored
     */
    public function hasToken(): bool
    {
        // Implement token existence check
        // Return true if a token exists, false otherwise
        return !empty($this->getToken());
    }

    /**
     * Increment the login attempt counter
     */
    public function incrementLoginAttempt(): int
    {
        // Implement login attempt increment logic
        // Return the new count
        return $newCount;
    }

    /**
     * Get the current login attempt count
     */
    public function getLoginAttemptCount(): int
    {
        // Implement login attempt count retrieval logic
        // Return the current count
        return $currentCount;
    }

    /**
     * Reset the login attempt counter to 0
     */
    public function resetLoginAttemptCount(): bool
    {
        // Implement login attempt reset logic
        // Return true if successful, false otherwise
        return true;
    }
}

// Then use your custom adapter with the CacheManager
$customAdapter = new CustomAdapter($yourStorageMechanism);
$cacheManager = new CacheManager($customAdapter, $apiKey);
```

## Exception Handling

When using the Fireboost PHP SDK, you may encounter the following exceptions:

### ApiException

Thrown when an error occurs during API communication. This exception is thrown by the following methods:
- `saveCache()` - When there's an error saving data to the cache
- `readCache()` - When there's an error reading data from the cache
- `deleteCache()` - When there's an error deleting data from the cache
- `readPublicCache()` - When there's an error reading public data from the cache
- `getStatistics()` - When there's an error retrieving cache statistics

The SDK automatically handles 401 Unauthorized errors by attempting to re-authenticate, but other API errors will be propagated to your code.

### InvalidArgumentException

Thrown in the following cases:
- When the API key is missing or invalid
- When the API key doesn't contain the required project information

### RuntimeException

Thrown in the following cases:
- When the maximum login attempts (3) are reached, indicating a potential issue with your API key
- When using the FileAdapter and the storage directory cannot be created or is not writable

### Redis-related Exceptions

When using the RedisAdapter, Redis-related exceptions (like connection failures) may be propagated to your code.

### Example of Exception Handling

```php
use FireboostIO\SDK\CacheManager;
use FireboostIO\ApiException;
use InvalidArgumentException;
use RuntimeException;

$cacheManager = new CacheManager();

try {
    $data = $cacheManager->readCache('example/key');
    // Process the data
} catch (ApiException $e) {
    // Handle API-related errors
    echo "API Error: " . $e->getMessage() . " (Code: " . $e->getCode() . ")";
} catch (InvalidArgumentException $e) {
    // Handle invalid argument errors
    echo "Invalid Argument: " . $e->getMessage();
} catch (RuntimeException $e) {
    // Handle runtime errors
    echo "Runtime Error: " . $e->getMessage();
} catch (\Exception $e) {
    // Handle any other unexpected exceptions
    echo "Unexpected Error: " . $e->getMessage();
}
```

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
