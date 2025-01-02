<?php

require_once(__DIR__ . '/../Engine/DatabaseConnection.php');

class DocumentDeathModel extends DatabaseConnection
{
    public function findAll($limit = 50, $offset = 0): array
    {
        $sql = "SELECT * FROM " . $this->table_prefix . "_dec3 LIMIT $limit OFFSET $offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findId(int $id): array
    {
        $sql = "SELECT * FROM " . $this->table_prefix . "_dec3 WHERE id=:id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $id
        ]);
        
        if ($document = $stmt->fetch()) {
            return $document;
        } 
        
        return null;
    }
}