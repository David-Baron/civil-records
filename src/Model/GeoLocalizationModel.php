<?php

namespace CivilRecords\Model;

use CivilRecords\Engine\DatabaseConnection;

class GeoLocalizationModel extends DatabaseConnection
{
    public function findAll($limit = 50, $offset = 0): array
    {
        $sql = "";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findOneByCriteria(array $criteria): array
    {
        $params = '';
        $i = 0;
        foreach ($criteria as $key => $value) {
            if ($i === 0) {
                $params .= "$key=:$key";
            } else {
                $params .= " AND $key=:$key";
            }
            $i++;
        }

        $sql = "SELECT * FROM " . $this->table_prefix . "_geoloc WHERE $params";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($criteria);
        if ($geoloc = $stmt->fetch()) {
            return $geoloc;
        }

        return null;
    }
}
