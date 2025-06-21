<?php

namespace FireboostIO\SDK\Adapter;

/**
 * Interface for JWT token storage adapters
 */
interface TokenStorageAdapterInterface
{
    /**
     * Store a JWT token
     *
     * @param string $token The JWT token to store
     * @return bool True if the token was stored successfully
     */
    public function storeToken(string $token): bool;

    /**
     * Retrieve the stored JWT token
     *
     * @return string|null The JWT token or null if no token is stored
     */
    public function getToken(): ?string;

    /**
     * Clear the stored JWT token
     *
     * @return bool True if the token was cleared successfully
     */
    public function clearToken(): bool;

    /**
     * Check if a token is stored
     *
     * @return bool True if a token is stored
     */
    public function hasToken(): bool;

    /**
     * Increment the login attempt counter
     *
     * @return int The new login attempt count
     */
    public function incrementLoginAttempt(): int;

    /**
     * Get the current login attempt count
     *
     * @return int The current login attempt count
     */
    public function getLoginAttemptCount(): int;

    /**
     * Reset the login attempt counter to 0
     *
     * @return bool True if the counter was reset successfully
     */
    public function resetLoginAttemptCount(): bool;
}
