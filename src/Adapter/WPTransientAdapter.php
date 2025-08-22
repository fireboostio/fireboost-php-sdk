<?php

namespace FireboostIO\SDK\Adapter;

/**
 * WordPress Transient-based implementation of the TokenStorageAdapterInterface
 * 
 * Uses WordPress transients for storing JWT tokens and login attempt counters.
 * Perfect for WordPress plugins and themes that need persistent token storage
 * across REST API calls and different PHP processes.
 * 
 * Note: This adapter requires WordPress to be loaded and WordPress functions
 * (set_transient, get_transient, delete_transient) to be available.
 */
class WPTransientAdapter implements TokenStorageAdapterInterface
{
    /**
     * @var string The transient key for storing the JWT token
     */
    private $tokenKey;

    /**
     * @var string The transient key for storing the login attempt count
     */
    private $loginAttemptKey;

    /**
     * @var int Time-to-live for the token in seconds (default: 1 hour)
     */
    private $tokenTtl;

    /**
     * @var int Time-to-live for the login attempt count in seconds (default: 24 hours)
     */
    private $loginAttemptTtl;

    /**
     * Constructor
     *
     * @param string $tokenKey The transient key for storing the JWT token
     * @param string $loginAttemptKey The transient key for storing the login attempt count
     * @param int $tokenTtl Time-to-live for the token in seconds (default: 1 hour)
     * @param int $loginAttemptTtl Time-to-live for the login attempt count in seconds (default: 24 hours)
     */
    public function __construct(
        string $tokenKey = 'fireboost_jwt_token',
        string $loginAttemptKey = 'fireboost_login_attempts',
        int $tokenTtl = 3600,
        int $loginAttemptTtl = 86400
    ) {
        $this->tokenKey = $tokenKey;
        $this->loginAttemptKey = $loginAttemptKey;
        $this->tokenTtl = $tokenTtl;
        $this->loginAttemptTtl = $loginAttemptTtl;
    }

    /**
     * {@inheritdoc}
     */
    public function storeToken(string $token): bool
    {
        return \set_transient($this->tokenKey, $token, $this->tokenTtl);
    }

    /**
     * {@inheritdoc}
     */
    public function getToken(): ?string
    {
        $token = \get_transient($this->tokenKey);
        return $token !== false ? $token : null;
    }

    /**
     * {@inheritdoc}
     */
    public function clearToken(): bool
    {
        return \delete_transient($this->tokenKey);
    }

    /**
     * {@inheritdoc}
     */
    public function hasToken(): bool
    {
        return \get_transient($this->tokenKey) !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function incrementLoginAttempt(): int
    {
        $count = $this->getLoginAttemptCount() + 1;
        \set_transient($this->loginAttemptKey, $count, $this->loginAttemptTtl);
        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function getLoginAttemptCount(): int
    {
        $count = \get_transient($this->loginAttemptKey);
        return $count !== false ? (int) $count : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function resetLoginAttemptCount(): bool
    {
        return \delete_transient($this->loginAttemptKey);
    }
}
