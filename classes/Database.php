<?php
/**
 * Database Class
 * Singleton PDO Database Connection
 */

class Database {
    private static $instance = null;
    private $connection;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

            $this->connection = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);

        } catch (PDOException $e) {
            // 운영 환경에서는 에러 로그만 기록
            error_log('Database Connection Error: ' . $e->getMessage());

            // 개발 환경에서만 에러 표시
            if (APP_ENV === 'development') {
                die('Database Connection Failed: ' . $e->getMessage());
            } else {
                die('Database connection error. Please contact administrator.');
            }
        }
    }

    /**
     * Get singleton instance
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get PDO connection
     * @return PDO
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Execute SELECT query and return single row
     * @param string $query SQL query with placeholders
     * @param array $params Parameters for prepared statement
     * @return array|false Single row or false
     */
    public function selectOne($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Database selectOne error: ' . $e->getMessage());
            error_log('Query: ' . $query);
            error_log('Params: ' . print_r($params, true));
            throw $e; // 에러를 다시 throw하여 상위에서 처리
        }
    }

    /**
     * Execute SELECT query and return all rows
     * @param string $query SQL query with placeholders
     * @param array $params Parameters for prepared statement
     * @return array Array of rows
     */
    public function select($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Database select error: ' . $e->getMessage());
            error_log('Query: ' . $query);
            error_log('Params: ' . print_r($params, true));
            throw $e; // 에러를 다시 throw하여 상위에서 처리
        }
    }

    /**
     * Execute INSERT/UPDATE/DELETE query
     * @param string $query SQL query with placeholders
     * @param array $params Parameters for prepared statement
     * @return int Number of affected rows
     */
    public function execute($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log('Database execute error: ' . $e->getMessage());
            error_log('Query: ' . $query);
            error_log('Params: ' . print_r($params, true));
            throw $e;
        }
    }

    /**
     * Insert query and return last insert ID
     * @param string $query SQL INSERT query with placeholders
     * @param array $params Parameters for prepared statement
     * @return int Last insert ID
     */
    public function insert($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $this->connection->lastInsertId();
        } catch (PDOException $e) {
            error_log("Database insert error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update query and return affected rows
     * @param string $query SQL UPDATE query with placeholders
     * @param array $params Parameters for prepared statement
     * @return int Number of affected rows
     */
    public function update($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Database update error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Execute a query (generic)
     * @param string $query SQL query
     * @return PDOStatement
     */
    public function query($query) {
        try {
            return $this->connection->query($query);
        } catch (PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Prepare statement
     */
    public function prepare($query) {
        return $this->connection->prepare($query);
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollback();
    }

    /**
     * Check if in transaction
     */
    public function inTransaction() {
        return $this->connection->inTransaction();
    }

    /**
     * Get last insert ID
     * @return string
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
