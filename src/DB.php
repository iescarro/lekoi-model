<?php

namespace Lekoi;

use Exception;

class DB
{
    static $db;

    public static function instance(): IDatabase
    {
        if (!self::$db) {
            throw new \Exception("Database not initialized. Call DB::init() first.");
        }
        return self::$db;
    }

    public static function init($config)
    {
        if (self::$db) return;
        switch ($config['driver']) {
            case 'mysqli':
                self::$db = new MySqlDatabase(
                    $config['host'],
                    $config['username'],
                    $config['password'],
                    $config['dbname']
                );
                break;
            case 'sqlite3':
                self::$db = new SQLiteDatabase($config['dbname']);
                break;
            default:
                throw new \Exception("Unsupported driver: " . $config['driver']);
        }
    }

    private static function ensure_init()
    {
        if (!self::$db) {
            self::init([
                'driver'   => getenv('DB_CONNECTION') ?: 'sqlite3',
                'host'     => getenv('DB_HOST'),
                'dbname'   => getenv('DB_DATABASE'),
                'username' => getenv('DB_USERNAME'),
                'password' => getenv('DB_PASSWORD'),
            ]);
        }
    }

    public static function insert(string $table, array $data): bool
    {
        self::ensure_init();
        return self::$db->insert($table, $data);
    }

    public static function update(string $table, array $data, array $where_clause): bool
    {
        self::ensure_init();
        return self::$db->update($table, $data, $where_clause);
    }

    public static function delete(string $table, array $where_clause): bool
    {
        self::ensure_init();
        return self::$db->delete($table, $where_clause);
    }

    public static function get(string $table_name, ?array $where_clause = null): IDatabase
    {
        self::ensure_init();
        return self::$db->get($table_name, $where_clause);
    }

    public static function row(): array
    {
        self::ensure_init();
        return self::$db->row();
    }

    public static function result(): array
    {
        self::ensure_init();
        return self::$db->result();
    }
}

interface IDatabase
{
    public function delete(string $table, array $where_clause): bool;
    public function insert(string $table, array $data): bool;
    public function update(string $table, array $data, array $where_clause): bool;
    public function get(string $table_name, ?array $where_clause = null): IDatabase;
    public function row(): ?object;
    public function result(): array;
}
