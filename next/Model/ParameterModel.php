<?php

require_once(__DIR__ . '/../Engine/DatabaseConnection.php');

class ParameterModel extends DatabaseConnection
{
    public function findAll(): array
    {
        $sql = "SELECT * FROM " . $this->table_prefix . "_params";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}