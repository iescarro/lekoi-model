<?php

namespace Lekoi;

class MySQLDatabase implements IDatabase
{
    protected $conn;

    public function __construct($host, $user, $pass, $dbname)
    {
        $this->conn = new \mysqli($host, $user, $pass, $dbname);
        if ($this->conn->connect_error) {
            throw new \Exception("MySQL Connection failed: " . $this->conn->connect_error);
        }
    }

    public function delete(string $table, array $where_clause): bool
    {
        if (empty($where_clause)) {
            throw new \InvalidArgumentException(
                "DELETE operation requires a non-empty WHERE clause for safety."
            );
        }

        $wheres = [];
        $values = [];
        $types = "";

        foreach ($where_clause as $column => $value) {
            $wheres[] = "`{$column}` = ?";
            $values[] = $value;
            // Detect parameter type for bind_param
            $types .= is_int($value) ? "i" : (is_float($value) ? "d" : "s");
        }

        $where_string = implode(" AND ", $wheres);
        $sql = "DELETE FROM `{$table}` WHERE {$where_string}";

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new \Exception("MySQL prepare error: " . $this->conn->error);
        }

        // Bind parameters dynamically
        $stmt->bind_param($types, ...$values);

        $success = $stmt->execute();

        if (!$success) {
            throw new \Exception("MySQL execute error: " . $stmt->error);
        }

        $affected = $stmt->affected_rows;

        $stmt->close();

        return $affected > 0;
    }

    public function get(string $table_name, ?array $where_clause = null): IDatabase
    {
        $sql = "SELECT * FROM `$table_name`";
        $params = [];
        $types = '';

        if (!empty($where_clause)) {
            $conditions = [];
            foreach ($where_clause as $column => $value) {
                // Prepare the WHERE clause structure: `column` = ?
                $conditions[] = "`{$column}` = ?";
                $params[] = $value;
                $types .= 's'; // Assume string type for binding
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            // Handle error if preparation fails (e.g., invalid table name)
            throw new \Exception("SQL Prepare Error: " . $this->conn->error);
        }

        if (!empty($params)) {
            // Note: PHP's mysqli::bind_param requires parameters to be passed by reference.
            // However, using the spread operator with bind_param handles this in modern PHP.
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();

        $result = $stmt->get_result();
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $stmt->close();

        // return $data;
        return $this;
    }

    public function row(): object
    {
        return (object)$this->row_array();
    }

    public function insert(string $table, array $data): bool
    {
        $columns = implode(",", array_keys($data));
        $placeholders = implode(",", array_fill(0, count($data), "?"));

        $stmt = $this->conn->prepare("INSERT INTO $table ($columns) VALUES ($placeholders)");
        if (!$stmt) throw new \Exception($this->conn->error);

        $types = str_repeat("s", count($data)); // all as string for simplicity
        $stmt->bind_param($types, ...array_values($data));

        return $stmt->execute();
    }

    public function update(string $table, array $data, array $where_clause): bool
    {
        if (empty($data) || empty($where_clause)) {
            throw new \InvalidArgumentException(
                "UPDATE requires both data and WHERE clause for safety."
            );
        }

        // Build SET clause
        $set_parts = [];
        $values = [];
        $types = '';

        foreach ($data as $column => $value) {
            $set_parts[] = "`{$column}` = ?";
            $values[] = $value;
            $types .= is_int($value) ? 'i' : (is_float($value) ? 'd' : 's');
        }

        // Build WHERE clause
        $where_parts = [];
        foreach ($where_clause as $column => $value) {
            $where_parts[] = "`{$column}` = ?";
            $values[] = $value;
            $types .= is_int($value) ? 'i' : (is_float($value) ? 'd' : 's');
        }

        $sql = "UPDATE `{$table}` SET " . implode(', ', $set_parts)
            . " WHERE " . implode(' AND ', $where_parts);

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new \Exception("MySQL prepare error: " . $this->conn->error);
        }

        // Bind parameters dynamically
        $stmt->bind_param($types, ...$values);

        $success = $stmt->execute();

        if (!$success) {
            throw new \Exception("MySQL execute error: " . $stmt->error);
        }

        $affected = $stmt->affected_rows;

        $stmt->close();

        return $affected > 0;
    }
}
