<?php
namespace CivilRecords\Domain;

use CivilRecords\Engine\DatabaseConnection;

class DeedMarriageModel extends DatabaseConnection
{
    public function findAll($limit = 50, $offset = 0): array
    {
        $sql = "SELECT * FROM " . $this->table_prefix . "_mar3 LIMIT $limit OFFSET $offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findId(int $id): array
    {
        $sql = "SELECT * FROM " . $this->table_prefix . "_mar3 WHERE id=:id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $id
        ]);

        if ($deed = $stmt->fetch()) {
            return $deed;
        } 
        
        return null;
    }
}