<?php

/**
 * Database Class
 * PDO Database Connection Handler
 * 
 * Handles database connection using PDO with support for multiple database types
 * (MySQL, MariaDB, PostgreSQL, SQLite, SQL Server)
 */

class Database
{
    private static $instance = null;
    private static $pdo = null;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
        // Singleton pattern
    }

    /**
     * Get singleton instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get PDO connection
     * 
     * @return PDO
     * @throws PDOException
     */
    public static function getConnection()
    {
        if (self::$pdo === null) {
            try {
                $config = require __DIR__ . '/../../config/db.php';

                // Create PDO instance
                self::$pdo = new PDO(
                    $config['dsn'],
                    $config['user'],
                    $config['pass'],
                    $config['options']
                );

                // Set error mode to exception
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                // Log error
                error_log("Database Connection Error: " . $e->getMessage());

                // In production, show generic error
                if (APP_ENV === 'production') {
                    throw new Exception("Database connection failed. Please contact administrator.");
                } else {
                    throw new Exception("Database Error: " . $e->getMessage());
                }
            }
        }

        return self::$pdo;
    }

    /**
     * Execute query with prepared statement
     * 
     * @param string $query SQL query
     * @param array $params Parameters for prepared statement
     * @return PDOStatement
     */
    public static function query($query, $params = [])
    {
        try {
            $pdo = self::getConnection();
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage() . " | Query: " . $query);
            throw $e;
        }
    }

    /**
     * Fetch single row
     * 
     * @param string $query SQL query
     * @param array $params Parameters
     * @return array|false
     */
    public static function fetchOne($query, $params = [])
    {
        $stmt = self::query($query, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch all rows
     * 
     * @param string $query SQL query
     * @param array $params Parameters
     * @return array
     */
    public static function fetchAll($query, $params = [])
    {
        $stmt = self::query($query, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Insert data
     * 
     * @param string $table Table name
     * @param array $data Associative array of data
     * @return int Last insert ID
     */
    public static function insert($table, $data)
    {
        $fields = array_keys($data);
        $values = array_values($data);

        $fieldList = implode(', ', $fields);
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));

        $query = "INSERT INTO {$table} ({$fieldList}) VALUES ({$placeholders})";

        self::query($query, $values);

        return self::getConnection()->lastInsertId();
    }

    /**
     * Update data
     * 
     * @param string $table Table name
     * @param array $data Data to update
     * @param array $where WHERE conditions
     * @return int Number of affected rows
     */
    public static function update($table, $data, $where)
    {
        $setClause = [];
        $values = [];

        foreach ($data as $field => $value) {
            $setClause[] = "{$field} = ?";
            $values[] = $value;
        }

        $whereClause = [];
        foreach ($where as $field => $value) {
            $whereClause[] = "{$field} = ?";
            $values[] = $value;
        }

        $query = "UPDATE {$table} SET " . implode(', ', $setClause) .
            " WHERE " . implode(' AND ', $whereClause);

        $stmt = self::query($query, $values);
        return $stmt->rowCount();
    }

    /**
     * Delete data
     * 
     * @param string $table Table name
     * @param array $where WHERE conditions
     * @return int Number of affected rows
     */
    public static function delete($table, $where)
    {
        $whereClause = [];
        $values = [];

        foreach ($where as $field => $value) {
            $whereClause[] = "{$field} = ?";
            $values[] = $value;
        }

        $query = "DELETE FROM {$table} WHERE " . implode(' AND ', $whereClause);

        $stmt = self::query($query, $values);
        return $stmt->rowCount();
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction()
    {
        return self::getConnection()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit()
    {
        return self::getConnection()->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback()
    {
        return self::getConnection()->rollBack();
    }

    /**
     * Check if table exists
     * 
     * @param string $table Table name
     * @return bool
     */
    public static function tableExists($table)
    {
        try {
            $result = self::getConnection()->query("SELECT 1 FROM {$table} LIMIT 1");
            return $result !== false;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get table columns
     * 
     * @param string $table Table name
     * @return array
     */
    public static function getTableColumns($table)
    {
        $query = "DESCRIBE {$table}";
        return self::fetchAll($query);
    }

    /**
     * Escape string (for legacy support, prefer prepared statements)
     * 
     * @param string $value Value to escape
     * @return string
     */
    public static function escape($value)
    {
        return self::getConnection()->quote($value);
    }

    /**
     * Close connection
     */
    public static function close()
    {
        self::$pdo = null;
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
