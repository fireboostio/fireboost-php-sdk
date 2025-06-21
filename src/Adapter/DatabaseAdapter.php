<?php

namespace FireboostIO\SDK\Adapter;

use PDO;
use PDOException;

/**
 * Database-based implementation of the TokenStorageAdapterInterface
 */
class DatabaseAdapter implements TokenStorageAdapterInterface
{
    /**
     * @var PDO The PDO database connection
     */
    private $pdo;

    /**
     * @var string The table name for storing tokens
     */
    private $tableName;

    /**
     * @var string The key identifier for this application/user
     */
    private $keyIdentifier;

    /**
     * Constructor
     *
     * @param PDO $pdo The PDO database connection
     * @param string $tableName The table name for storing tokens
     * @param string $keyIdentifier The key identifier for this application/user
     */
    public function __construct(
        PDO $pdo,
        string $tableName = 'fireboost_tokens',
        string $keyIdentifier = 'default'
    ) {
        $this->pdo = $pdo;
        $this->tableName = $tableName;
        $this->keyIdentifier = $keyIdentifier;

        // Ensure the table exists
        $this->createTableIfNotExists();
    }

    /**
     * {@inheritdoc}
     */
    public function storeToken(string $token): bool
    {
        $this->ensureRecordExists();

        $sql = "UPDATE {$this->tableName} 
                SET token = :token, updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id";

        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                'token' => $token,
                'id' => $this->keyIdentifier
            ]);
        } catch (PDOException $e) {
            error_log("Failed to store token: " . $e->getMessage());
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getToken(): ?string
    {
        $sql = "SELECT token FROM {$this->tableName} WHERE id = :id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id' => $this->keyIdentifier]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result && $result['token'] ? $result['token'] : null;
        } catch (PDOException $e) {
            error_log("Failed to get token: " . $e->getMessage());
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clearToken(): bool
    {
        $sql = "UPDATE {$this->tableName} 
                SET token = NULL, updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id";

        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute(['id' => $this->keyIdentifier]);
        } catch (PDOException $e) {
            error_log("Failed to clear token: " . $e->getMessage());
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasToken(): bool
    {
        $token = $this->getToken();
        return $token !== null && $token !== '';
    }

    /**
     * {@inheritdoc}
     */
    public function incrementLoginAttempt(): int
    {
        $this->ensureRecordExists();

        $sql = "UPDATE {$this->tableName} 
                SET login_attempts = login_attempts + 1, updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id' => $this->keyIdentifier]);

            return $this->getLoginAttemptCount();
        } catch (PDOException $e) {
            error_log("Failed to increment login attempts: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLoginAttemptCount(): int
    {
        $sql = "SELECT login_attempts FROM {$this->tableName} WHERE id = :id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id' => $this->keyIdentifier]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? (int)$result['login_attempts'] : 0;
        } catch (PDOException $e) {
            error_log("Failed to get login attempts: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function resetLoginAttemptCount(): bool
    {
        $sql = "UPDATE {$this->tableName} 
                SET login_attempts = 0, updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id";

        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute(['id' => $this->keyIdentifier]);
        } catch (PDOException $e) {
            error_log("Failed to reset login attempts: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create the tokens table if it doesn't exist
     */
    private function createTableIfNotExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
            id VARCHAR(255) PRIMARY KEY,
            token TEXT NULL,
            login_attempts INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            // Log the error or handle it as appropriate for your application
            error_log("Failed to create token table: " . $e->getMessage());
        }
    }

    /**
     * Ensure the record exists for the current key identifier
     */
    private function ensureRecordExists(): void
    {
        $sql = "INSERT IGNORE INTO {$this->tableName} (id, token, login_attempts) 
                VALUES (:id, NULL, 0)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id' => $this->keyIdentifier]);
        } catch (PDOException $e) {
            // Log the error or handle it as appropriate for your application
            error_log("Failed to ensure record exists: " . $e->getMessage());
        }
    }
}
