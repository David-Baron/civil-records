<?php

namespace CivilRecords\Engine;


class DatabaseConnection
{
    protected \PDO $db;
    protected string $table_prefix = 'cr';

    public function __construct()
    {
        $this->db = new \PDO("mysql:host=$_ENV[DB_HOST];dbname=$_ENV[DB_NAME];charset=utf8mb4", $_ENV['DB_USER'], $_ENV['DB_PASS']);
        $this->db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'dev') {
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
        if (isset($_ENV['DB_TABLES_PREFIX'])) {
            $this->table_prefix = $_ENV['DB_TABLES_PREFIX'];
        }
    }
}
