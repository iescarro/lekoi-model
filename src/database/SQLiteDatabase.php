<?php

namespace Lekoi;

class SQLiteDatabase implements IDatabase
{
    protected $conn;
    private $stmt;
    private $result;

    public function __construct($file)
    {
        $this->conn = new \SQLite3($file);
    }

    public function delete(string $table, array $where_clause): bool
    {
        if (empty($where_clause)) {
            // Safety measure: prevent a full table DELETE by requiring a condition.
            throw new \InvalidArgumentException("DELETE operation requires a non-empty WHERE clause for safety.");
        }

        $wheres = [];
        $i = 0;
        foreach (array_keys($where_clause) as $column) {
            $wheres[] = "{$column} = :w{$i}";
            $i++;
        }

        $where_string = implode(' AND ', $wheres);
        $sql = "DELETE FROM {$table} WHERE {$where_string}";

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new \Exception("SQLite prepare error: " . $this->conn->lastErrorMsg());
        }

        $i = 0;
        foreach ($where_clause as $value) {
            // Using SQLITE3_TEXT is generally safe for comparison values
            $stmt->bindValue(":w{$i}", $value, SQLITE3_TEXT);
            $i++;
        }

        $result = $stmt->execute();

        // Check how many rows were affected
        $affectedRows = $this->conn->changes();

        // Clean up the statement resource
        $stmt->close();

        return $affectedRows > 0; // Return true if rows were deleted
    }

    public function get(string $table_name, ?array $where_clause = null): IDatabase
    {
        $sql = "SELECT * FROM {$table_name}";
        $params = [];

        if (!empty($where_clause)) {
            $conditions = [];
            foreach ($where_clause as $column => $value) {
                // Use question mark placeholders for binding
                $conditions[] = "`{$column}` = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $this->stmt = $this->conn->prepare($sql);
        if (!$this->stmt) {
            // Error handling (e.g., table doesn't exist)
            // You might want to log this or throw a custom exception
            throw new \Exception("SQL Prepare Error: " . $this->conn->lastErrorMsg());
        }

        $i = 1;
        foreach ($params as $value) {
            // SQLite3::bindValue takes the parameter index (starting at 1) and the value.
            // Using SQLITE3_TEXT type for all values for simplicity in this minimal example.
            $this->stmt->bindValue($i++, $value, SQLITE3_TEXT);
        }

        $this->result = $this->stmt->execute();

        return $this;
    }

    public function result(): array
    {
        $data = [];
        if ($this->result) {
            while ($row = $this->result->fetchArray(SQLITE3_ASSOC)) {
                $data[] = (object)$row;
            }
        }
        $this->stmt->close();
        return $data;
    }

    public function row_array()
    {
        $data = null;
        if ($this->result) {
            // The SQLITE3_ASSOC flag ensures the result is an associative array.
            if ($row = $this->result->fetchArray(SQLITE3_ASSOC)) {
                $data = $row;
            }
        }
        $this->stmt->close();
        return $data;
    }

    public function row(): ?object
    {
        $row = $this->row_array();
        return $row ? (object)$row : null;
    }

    public function insert(string $table, array $data): bool
    {
        $columns = implode(",", array_keys($data));
        $placeholders = ":" . implode(",:", array_keys($data));

        $stmt = $this->conn->prepare("INSERT INTO $table ($columns) VALUES ($placeholders)");
        foreach ($data as $key => $val) {
            $stmt->bindValue(":$key", $val, SQLITE3_TEXT);
        }

        return $stmt->execute() !== false;
    }

    public function update(string $table, array $data, array $where_clause): bool
    {
        if (empty($data) || empty($where_clause)) {
            throw new \InvalidArgumentException(
                "UPDATE requires both data and WHERE clause for safety."
            );
        }

        $set_parts = [];
        $where_parts = [];
        $params = [];

        // Build SET clause
        foreach ($data as $column => $value) {
            $placeholder = ":set_{$column}";
            $set_parts[] = "`{$column}` = {$placeholder}";
            $params[$placeholder] = $value;
        }

        // Build WHERE clause
        foreach ($where_clause as $column => $value) {
            $placeholder = ":where_{$column}";
            $where_parts[] = "`{$column}` = {$placeholder}";
            $params[$placeholder] = $value;
        }

        $sql = "UPDATE `{$table}` SET " . implode(', ', $set_parts)
            . " WHERE " . implode(' AND ', $where_parts);

        // Debugging (optional)
        // echo $sql . PHP_EOL;

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new \Exception("SQLite prepare error: " . $this->conn->lastErrorMsg());
        }

        // Bind all parameters (SET + WHERE)
        foreach ($params as $placeholder => $value) {
            $stmt->bindValue($placeholder, $value, SQLITE3_TEXT);
        }

        $result = $stmt->execute();

        if ($result === false) {
            throw new \Exception("SQLite execute error: " . $this->conn->lastErrorMsg());
        }

        $affectedRows = $this->conn->changes();

        $stmt->close();

        return $affectedRows > 0;
    }
}
