<?php

namespace FireboostIO\SDK;

use FireboostIO\Api\FireboostApi;
use FireboostIO\Model\InlineResponse200;
use FireboostIO\Model\InlineResponse401;
use FireboostIO\Model\InlineResponse404;
use FireboostIO\Model\InlineResponse423;
use FireboostIO\Model\SetInput;
use FireboostIO\Model\LoginInput;
use FireboostIO\ApiException;
use FireboostIO\SDK\Adapter\TokenStorageAdapterInterface;
use FireboostIO\SDK\Adapter\SessionAdapter;
use FireboostIO\Encryptor\CredentialExtractor;
use FireboostIO\Encryptor\ApiKeyExtractor;
use InvalidArgumentException;
use RuntimeException;

/**
 * CacheManager Class
 *
 * Provides methods for managing cache operations with the FireBoost API
 */
class CacheManager
{
    /**
     * API URL template with project placeholder
     */
    private const API_URL_TEMPLATE = 'https://{project}.api.fireboost.io';

    /**
     * @var FireboostApi The API client instance
     */
    private FireboostApi $api;

    /**
     * @var string|null The API key
     */
    private $apiKey;

    /**
     * @var TokenStorageAdapterInterface The token storage adapter
     */
    private $adapter;

    /**
     * @var CredentialExtractor The credential extractor
     */
    private CredentialExtractor $credentialExtractor;

    /**
     * @var ApiKeyExtractor The API key extractor
     */
    private ApiKeyExtractor $apiKeyExtractor;

    /**
     * CacheManager constructor
     *
     * @param TokenStorageAdapterInterface|null $adapter The token storage adapter (optional, defaults to SessionAdapter)
     * @param string|null $apiKey The API key (optional, falls back to FIREBOOST_API_KEY env variable)
     */
    public function __construct(
        ?TokenStorageAdapterInterface $adapter = null,
        ?string $apiKey = null
    ) {
        $this->api = new FireboostApi();
        $this->apiKey = $apiKey ?? getenv('FIREBOOST_API_KEY');
        $this->adapter = $adapter ?? new SessionAdapter();
        $this->credentialExtractor = new CredentialExtractor();
        $this->apiKeyExtractor = new ApiKeyExtractor();
    }

    /**
     * Save data to the cache
     *
     * @param string $cacheKey The cache key
     * @param mixed $content The content to cache
     * @param bool $isPublic Whether the cache is publicly accessible
     * @return mixed The API response
     * @throws ApiException If an API error occurs
     */
    public function saveCache(string $cacheKey, $content, bool $isPublic = false)
    {
        if (!$this->adapter->hasToken()) {
            $this->login();
        }

        $setInput = new SetInput([
            'cache_key' => $cacheKey,
            'content' => $content,
            'is_public' => $isPublic
        ]);

        try {
            $this->api->getConfig()->setHost($this->getApiUrl());
            $this->api->getConfig()->setApiKeyPrefix('bearer', 'Bearer');
            $this->api->getConfig()->setAccessToken($this->adapter->getToken());

            return $this->api->setCache($setInput);
        } catch (ApiException $e) {
            if ($e->getCode() == 401) {
                $this->login();

                $this->api->getConfig()->setAccessToken($this->adapter->getToken());

                return $this->api->setCache($setInput);
            }

            throw $e;
        }
    }

    /**
     * Read data from the cache
     *
     * @param string $cacheKey The cache key
     * @return InlineResponse401|InlineResponse404|InlineResponse423|object The cached content
     * @throws ApiException If an API error occurs
     */
    public function readCache(string $cacheKey)
    {
        if (!$this->adapter->hasToken()) {
            $this->login();
        }

        try {
            $this->api->getConfig()->setHost($this->getApiUrl());
            $this->api->getConfig()->setApiKeyPrefix('bearer', 'Bearer');
            $this->api->getConfig()->setAccessToken($this->adapter->getToken());

            return $this->api->getCache($cacheKey);
        } catch (ApiException $e) {
            if ($e->getCode() == 401) {
                $this->login();

                $this->api->getConfig()->setAccessToken($this->adapter->getToken());

                return $this->api->getCache($cacheKey);
            }

            throw $e;
        }
    }

    /**
     * Delete data from the cache
     *
     * @param string $cacheKey The cache key
     * @return void
     * @throws ApiException If an API error occurs
     */
    public function deleteCache(string $cacheKey)
    {
        if (!$this->adapter->hasToken()) {
            $this->login();
        }

        try {
            $this->api->getConfig()->setHost($this->getApiUrl());
            $this->api->getConfig()->setApiKeyPrefix('bearer', 'Bearer');
            $this->api->getConfig()->setAccessToken($this->adapter->getToken());

            $this->api->deleteCache($cacheKey);
        } catch (ApiException $e) {
            if ($e->getCode() == 401) {
                $this->login();

                $this->api->getConfig()->setAccessToken($this->adapter->getToken());
                $this->api->deleteCache($cacheKey);
            }

            throw $e;
        }
    }

    /**
     * Delete all data from the cache
     *
     * @return void
     * @throws ApiException If an API error occurs
     */
    public function deleteAllCache()
    {
        if (!$this->adapter->hasToken()) {
            $this->login();
        }

        try {
            $this->api->getConfig()->setHost($this->getApiUrl());
            $this->api->getConfig()->setApiKeyPrefix('bearer', 'Bearer');
            $this->api->getConfig()->setAccessToken($this->adapter->getToken());

            $this->api->deleteAllCache();
        } catch (ApiException $e) {
            if ($e->getCode() == 401) {
                $this->login();

                $this->api->getConfig()->setAccessToken($this->adapter->getToken());
                $this->api->deleteAllCache();
            }

            throw $e;
        }
    }

    /**
     * Read publicly accessible data from the cache
     *
     * This method does not require authentication
     *
     * @param string $cacheKey The cache key
     * @return InlineResponse401|InlineResponse404|InlineResponse423|object The cached content
     * @throws ApiException If an API error occurs
     */
    public function readPublicCache(string $cacheKey)
    {
        $this->api->getConfig()->setHost($this->getApiUrl());
        $this->api->getConfig()->setAccessToken(null);

        return $this->api->publicGetCache($cacheKey);
    }

    /**
     * Get cache usage statistics
     *
     * @return InlineResponse200|InlineResponse401|InlineResponse404|InlineResponse423 The cache statistics including read and write data
     * @throws ApiException If an API error occurs
     */
    public function getStatistics()
    {
        if (!$this->adapter->hasToken()) {
            $this->login();
        }

        try {
            $this->api->getConfig()->setHost($this->getApiUrl());
            $this->api->getConfig()->setApiKeyPrefix('bearer', 'Bearer');
            $this->api->getConfig()->setAccessToken($this->adapter->getToken());

            return $this->api->getTrackingData();
        } catch (ApiException $e) {
            if ($e->getCode() == 401) {
                $this->login();

                $this->api->getConfig()->setAccessToken($this->adapter->getToken());

                return $this->api->getTrackingData();
            }

            throw $e;
        }
    }

    /**
     * Get the API URL based on the project extracted from the API key
     *
     * @return string The API URL
     * @throws InvalidArgumentException If the API key is invalid or missing
     */
    private function getApiUrl(): string
    {
        if (empty($this->apiKey)) {
            throw new InvalidArgumentException('API key is required to determine API URL');
        }

        $payload = $this->apiKeyExtractor->getApiKeyPayload($this->apiKey);

        if (!isset($payload['project'])) {
            throw new InvalidArgumentException('Invalid API key: missing project information');
        }

        return str_replace('{project}', $payload['project'], self::API_URL_TEMPLATE);
    }

    /**
     * Perform login and get JWT token
     *
     * Will attempt to log in up to 3 times before giving up to prevent infinite login loops
     *
     * @return void
     * @throws ApiException If an API error occurs or maximum login attempts reached
     */
    private function login(): void
    {
        if (empty($this->apiKey)) {
            throw new InvalidArgumentException('API key is required for login');
        }

        $attemptCount = $this->adapter->getLoginAttemptCount();
        if ($attemptCount >= 3) {
            throw new RuntimeException('Please verify your API key. Something is wrong with the key. Please contact the Fireboost support for assistance.');
        }

        $this->adapter->incrementLoginAttempt();

        // Set the API URL based on the project in the API key
        $this->api->getConfig()->setHost($this->getApiUrl());

        $loginInput = new LoginInput($this->credentialExtractor->getLoginInputData($this->apiKey));

        $loginOutput = $this->api->login($loginInput);
        $this->adapter->storeToken($loginOutput->getJwtToken());

        $this->adapter->resetLoginAttemptCount();
    }
}
