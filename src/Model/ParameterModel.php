<?php
namespace CivilRecords\Model;

use CivilRecords\Engine\DatabaseConnection;

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