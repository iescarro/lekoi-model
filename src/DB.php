<?php

namespace Lekoi;

use Exception;

class DB
{
    static $db;

    static function init($config)
    {
        switch ($config['driver']) {
            case 'mysql':
                self::$db = new MySQLDatabase($config['host'], $config['username'], $config['password'], $config['dbname']);
                break;
            case 'sqlite3':
                self::$db = new SQLiteDatabase($config['dbname']);
                break;
            default:
                throw new \Exception("Unsupported driver: " . $config['driver']);
        }
    }

    static function insert(string $table, array $data): bool
    {
        return self::$db->insert($table, $data);
    }

    static function get(string $table_name, ?array $where_clause = null): IDatabase
    {
        return self::$db->get($table_name, $where_clause);
    }

    static function row(): array
    {
        return self::$db->row();
    }

    static function result(): array
    {
        return self::$db->result();
    }
}

interface IDatabase
{
    public function delete(string $table, array $where_clause): bool;
    public function insert(string $table, array $data): bool;
    public function update(string $table, array $data, array $where_clause): bool;
    public function get(string $table_name, ?array $where_clause = null): IDatabase;
    public function row(): object;
    public function result(): array;
}
