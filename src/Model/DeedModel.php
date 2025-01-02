<?php
namespace CivilRecords\Model;

use CivilRecords\Engine\DatabaseConnection;

class DeedModel extends DatabaseConnection
{
    public function findAll($limit = 50, $offset = 0): array
    {
        $sql = "";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}