<?php

namespace CivilRecords\Domain;

use CivilRecords\Engine\DatabaseConnection;

class StatisticModel extends DatabaseConnection
{
    public function findAll(): array
    {
        $sql = "SELECT * FROM " . $this->table_prefix . "_sums";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findAllForMap($deedType = 'A'): array
    {
        $sql_params = '';
        if ($deedType != 'A') {
            $sql_params = " WHERE TYPACT='" . $deedType . "'";
        }

        $sql = "SELECT DEPART, COMMUNE, TYPACT, LIBELLE, sum(NB_TOT) AS NB_TOT 
        FROM " . $this->table_prefix . "_sums " . $sql_params . " 
        GROUP BY DEPART, COMMUNE, TYPACT, LIBELLE 
        ORDER BY DEPART, COMMUNE, INSTR('NMDV', TYPACT), LIBELLE;";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
