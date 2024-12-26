<?php

require_once(__DIR__ . '/../Engine/DatabaseConnection.php');

class DeedModel extends DatabaseConnection
{
    public function findAll($limit = 50, $offset = 0)
    {
        $sql = "";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}