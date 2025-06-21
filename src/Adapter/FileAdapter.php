<?php

namespace FireboostIO\SDK\Adapter;

/**
 * File-based implementation of the TokenStorageAdapterInterface
 */
class FileAdapter implements TokenStorageAdapterInterface
{
    /**
     * @var string The directory path where token files will be stored
     */
    private $storagePath;

    /**
     * @var string The file name for the token
     */
    private $tokenFileName;

    /**
     * @var string The file name for the login attempt count
     */
    private $loginAttemptsFileName;

    /**
     * Constructor
     *
     * @param string|null $storagePath The directory path where token files will be stored (defaults to system temp directory)
     * @param string $tokenFileName The file name for the token
     * @param string $loginAttemptsFileName The file name for the login attempt count
     */
    public function __construct(
        ?string $storagePath = null,
        string $tokenFileName = 'fireboost_token',
        string $loginAttemptsFileName = 'fireboost_login_attempts'
    ) {
        $this->storagePath = $storagePath ?? sys_get_temp_dir();
        $this->tokenFileName = $tokenFileName;
        $this->loginAttemptsFileName = $loginAttemptsFileName;

        // Ensure the storage directory exists and is writable
        $this->ensureStorageDirectoryExists();
    }

    /**
     * {@inheritdoc}
     */
    public function storeToken(string $token): bool
    {
        return file_put_contents($this->getTokenFilePath(), $token) !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function getToken(): ?string
    {
        $filePath = $this->getTokenFilePath();

        if (!file_exists($filePath)) {
            return null;
        }

        $token = file_get_contents($filePath);
        return $token !== false ? $token : null;
    }

    /**
     * {@inheritdoc}
     */
    public function clearToken(): bool
    {
        $filePath = $this->getTokenFilePath();

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function hasToken(): bool
    {
        $filePath = $this->getTokenFilePath();
        return file_exists($filePath) && filesize($filePath) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function incrementLoginAttempt(): int
    {
        $count = $this->getLoginAttemptCount() + 1;
        file_put_contents($this->getLoginAttemptsFilePath(), (string) $count);
        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function getLoginAttemptCount(): int
    {
        $filePath = $this->getLoginAttemptsFilePath();

        if (!file_exists($filePath)) {
            return 0;
        }

        $content = file_get_contents($filePath);
        return $content !== false ? (int) $content : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function resetLoginAttemptCount(): bool
    {
        return file_put_contents($this->getLoginAttemptsFilePath(), '0') !== false;
    }

    /**
     * Ensure the storage directory exists and is writable
     *
     * @throws \RuntimeException If the directory cannot be created or is not writable
     */
    private function ensureStorageDirectoryExists(): void
    {
        if (!file_exists($this->storagePath)) {
            if (!mkdir($this->storagePath, 0755, true)) {
                throw new \RuntimeException("Failed to create storage directory: {$this->storagePath}");
            }
        }

        if (!is_writable($this->storagePath)) {
            throw new \RuntimeException("Storage directory is not writable: {$this->storagePath}");
        }
    }

    /**
     * Get the full path to the token file
     *
     * @return string The full path to the token file
     */
    private function getTokenFilePath(): string
    {
        return $this->storagePath . DIRECTORY_SEPARATOR . $this->tokenFileName;
    }

    /**
     * Get the full path to the login attempts file
     *
     * @return string The full path to the login attempts file
     */
    private function getLoginAttemptsFilePath(): string
    {
        return $this->storagePath . DIRECTORY_SEPARATOR . $this->loginAttemptsFileName;
    }
}
