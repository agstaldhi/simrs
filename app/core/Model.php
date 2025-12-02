<?php

/**
 * Base Model Class
 * 
 * Provides common database operations for all models
 */

class Model
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find record by ID
     * 
     * @param int $id Record ID
     * @return array|false
     */
    public function find($id)
    {
        $query = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1";
        return Database::fetchOne($query, [$id]);
    }

    /**
     * Find all records
     * 
     * @param array $conditions WHERE conditions
     * @param string $orderBy ORDER BY clause
     * @param int|null $limit LIMIT
     * @param int|null $offset OFFSET
     * @return array
     */
    public function findAll($conditions = [], $orderBy = null, $limit = null, $offset = null)
    {
        $query = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $whereClauses = [];
            foreach ($conditions as $field => $value) {
                $whereClauses[] = "{$field} = ?";
                $params[] = $value;
            }
            $query .= " WHERE " . implode(' AND ', $whereClauses);
        }

        if ($orderBy) {
            $query .= " ORDER BY {$orderBy}";
        }

        if ($limit) {
            $query .= " LIMIT {$limit}";
            if ($offset) {
                $query .= " OFFSET {$offset}";
            }
        }

        return Database::fetchAll($query, $params);
    }

    /**
     * Find one record by conditions
     * 
     * @param array $conditions WHERE conditions
     * @return array|false
     */
    public function findOne($conditions)
    {
        $whereClauses = [];
        $params = [];

        foreach ($conditions as $field => $value) {
            $whereClauses[] = "{$field} = ?";
            $params[] = $value;
        }

        $query = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $whereClauses) . " LIMIT 1";
        return Database::fetchOne($query, $params);
    }

    /**
     * Insert new record
     * 
     * @param array $data Data to insert
     * @return int Last insert ID
     */
    public function create($data)
    {
        // Add created_at if not exists
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        return Database::insert($this->table, $data);
    }

    /**
     * Update record
     * 
     * @param int $id Record ID
     * @param array $data Data to update
     * @return int Number of affected rows
     */
    public function update($id, $data)
    {
        // Add updated_at if not exists
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        return Database::update($this->table, $data, [$this->primaryKey => $id]);
    }

    /**
     * Update by conditions
     * 
     * @param array $data Data to update
     * @param array $conditions WHERE conditions
     * @return int Number of affected rows
     */
    public function updateWhere($data, $conditions)
    {
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        return Database::update($this->table, $data, $conditions);
    }

    /**
     * Delete record
     * 
     * @param int $id Record ID
     * @return int Number of affected rows
     */
    public function delete($id)
    {
        return Database::delete($this->table, [$this->primaryKey => $id]);
    }

    /**
     * Delete by conditions
     * 
     * @param array $conditions WHERE conditions
     * @return int Number of affected rows
     */
    public function deleteWhere($conditions)
    {
        return Database::delete($this->table, $conditions);
    }

    /**
     * Count records
     * 
     * @param array $conditions WHERE conditions
     * @return int
     */
    public function count($conditions = [])
    {
        $query = "SELECT COUNT(*) as total FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $whereClauses = [];
            foreach ($conditions as $field => $value) {
                $whereClauses[] = "{$field} = ?";
                $params[] = $value;
            }
            $query .= " WHERE " . implode(' AND ', $whereClauses);
        }

        $result = Database::fetchOne($query, $params);
        return (int)$result['total'];
    }

    /**
     * Check if record exists
     * 
     * @param array $conditions WHERE conditions
     * @return bool
     */
    public function exists($conditions)
    {
        return $this->count($conditions) > 0;
    }

    /**
     * Execute custom query
     * 
     * @param string $query SQL query
     * @param array $params Parameters
     * @return PDOStatement
     */
    public function query($query, $params = [])
    {
        return Database::query($query, $params);
    }

    /**
     * Fetch all with custom query
     * 
     * @param string $query SQL query
     * @param array $params Parameters
     * @return array
     */
    public function fetchAll($query, $params = [])
    {
        return Database::fetchAll($query, $params);
    }

    /**
     * Fetch one with custom query
     * 
     * @param string $query SQL query
     * @param array $params Parameters
     * @return array|false
     */
    public function fetchOne($query, $params = [])
    {
        return Database::fetchOne($query, $params);
    }

    /**
     * Begin transaction
     */
    public function beginTransaction()
    {
        return Database::beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit()
    {
        return Database::commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback()
    {
        return Database::rollback();
    }

    /**
     * Paginate results
     * 
     * @param int $page Current page
     * @param int $perPage Items per page
     * @param array $conditions WHERE conditions
     * @param string $orderBy ORDER BY clause
     * @return array
     */
    public function paginate($page = 1, $perPage = 20, $conditions = [], $orderBy = null)
    {
        $offset = ($page - 1) * $perPage;

        $total = $this->count($conditions);
        $totalPages = ceil($total / $perPage);

        $data = $this->findAll($conditions, $orderBy, $perPage, $offset);

        return [
            'data' => $data,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ];
    }

    /**
     * Soft delete (set is_active = 0)
     * 
     * @param int $id Record ID
     * @return int
     */
    public function softDelete($id)
    {
        return $this->update($id, ['is_active' => 0]);
    }

    /**
     * Restore soft deleted record
     * 
     * @param int $id Record ID
     * @return int
     */
    public function restore($id)
    {
        return $this->update($id, ['is_active' => 1]);
    }

    /**
     * Get table name
     * 
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Set table name
     * 
     * @param string $table Table name
     */
    public function setTable($table)
    {
        $this->table = $table;
    }
}
