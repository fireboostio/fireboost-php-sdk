<?php

namespace FireboostIO\SDK\Adapter;

/**
 * Session-based implementation of the TokenStorageAdapterInterface
 */
class SessionAdapter implements TokenStorageAdapterInterface
{
    /**
     * @var string The session key for storing the JWT token
     */
    private $sessionKey;

    /**
     * @var string The session key for storing the login attempt count
     */
    private $loginAttemptKey;

    /**
     * Constructor
     *
     * @param string $sessionKey The session key for storing the JWT token
     * @param string $loginAttemptKey The session key for storing the login attempt count
     */
    public function __construct(
        string $sessionKey = 'fireboost_jwt_token',
        string $loginAttemptKey = 'fireboost_login_attempts'
    ) {
        $this->sessionKey = $sessionKey;
        $this->loginAttemptKey = $loginAttemptKey;

        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function storeToken(string $token): bool
    {
        $_SESSION[$this->sessionKey] = $token;
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getToken(): ?string
    {
        return $_SESSION[$this->sessionKey] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function clearToken(): bool
    {
        if (isset($_SESSION[$this->sessionKey])) {
            unset($_SESSION[$this->sessionKey]);
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function hasToken(): bool
    {
        return isset($_SESSION[$this->sessionKey]) && !empty($_SESSION[$this->sessionKey]);
    }

    /**
     * {@inheritdoc}
     */
    public function incrementLoginAttempt(): int
    {
        $count = $this->getLoginAttemptCount() + 1;
        $_SESSION[$this->loginAttemptKey] = $count;
        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function getLoginAttemptCount(): int
    {
        return $_SESSION[$this->loginAttemptKey] ?? 0;
    }

    /**
     * {@inheritdoc}
     */
    public function resetLoginAttemptCount(): bool
    {
        $_SESSION[$this->loginAttemptKey] = 0;
        return true;
    }
}
