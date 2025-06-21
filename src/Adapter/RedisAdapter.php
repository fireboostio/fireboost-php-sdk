<?php

namespace FireboostIO\SDK\Adapter;

/**
 * Redis-based implementation of the TokenStorageAdapterInterface
 */
class RedisAdapter implements TokenStorageAdapterInterface
{
    /**
     * @var \Redis The Redis client instance
     */
    private $redis;

    /**
     * @var string The Redis key prefix for storing the JWT token
     */
    private $tokenKey;

    /**
     * @var string The Redis key prefix for storing the login attempt count
     */
    private $loginAttemptKey;

    /**
     * @var int Time-to-live for the token in seconds (default: 1 hour)
     */
    private $ttl;

    /**
     * Constructor
     *
     * @param \Redis|null $redis The Redis client instance
     * @param string $tokenKey The Redis key prefix for storing the JWT token
     * @param string $loginAttemptKey The Redis key prefix for storing the login attempt count
     * @param int $ttl Time-to-live for the token in seconds (default: 1 hour)
     */
    public function __construct(
        ?\Redis $redis = null,
        string $tokenKey = 'fireboost:jwt_token:',
        string $loginAttemptKey = 'fireboost:login_attempts:',
        int $ttl = 3600
    ) {
        if ($redis === null) {
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6379);
        }

        $this->redis = $redis;
        $this->tokenKey = $tokenKey;
        $this->loginAttemptKey = $loginAttemptKey;
        $this->ttl = $ttl;
    }

    /**
     * {@inheritdoc}
     */
    public function storeToken(string $token): bool
    {
        return $this->redis->set($this->tokenKey, $token, $this->ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function getToken(): ?string
    {
        $token = $this->redis->get($this->tokenKey);
        return $token !== false ? $token : null;
    }

    /**
     * {@inheritdoc}
     */
    public function clearToken(): bool
    {
        return $this->redis->del($this->tokenKey) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function hasToken(): bool
    {
        return $this->redis->exists($this->tokenKey) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function incrementLoginAttempt(): int
    {
        $count = $this->redis->incr($this->loginAttemptKey);
        // Set expiration for login attempt counter to prevent orphaned counters
        $this->redis->expire($this->loginAttemptKey, $this->ttl);
        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function getLoginAttemptCount(): int
    {
        $count = $this->redis->get($this->loginAttemptKey);
        return $count !== false ? (int)$count : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function resetLoginAttemptCount(): bool
    {
        return $this->redis->set($this->loginAttemptKey, 0, $this->ttl);
    }
}