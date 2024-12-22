<?php

require_once(__DIR__ . '/../Engine/DatabaseConnection.php');

class UserModel extends DatabaseConnection
{
    public function findAllByCriteria(array $criteria)
    {
        $params = '';
        $i = 0;
        foreach ($criteria as $key => $value) {
            if ($i === 0) {
                $params .= "$key=:$key";
            } else {
                $params .= " AND $key=:$key";
            }
        }

        $sql = "SELECT * FROM " . $this->table_prefix . "_user3 WHERE $params ORDER BY NOM,PRENOM";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($criteria);
        return $stmt->fetchAll();
    }

    public function findOneByCriteria(array $criteria)
    {
        $params = '';
        $i = 0;
        foreach ($criteria as $key => $value) {
            if ($i === 0) {
                $params .= "$key=:$key";
            } else {
                $params .= " AND $key=:$key";
            }
        }

        $sql = "SELECT * FROM " . $this->table_prefix . "_user3 WHERE $params ORDER BY NOM,PRENOM";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($criteria);
        if ($user = $stmt->fetch()) {
            return $user;
        }

        return null;
    }

    public function findAllWithMinLevel(int $minUserlevel)
    {
        $sql = "SELECT * FROM " . $this->table_prefix . "_user3 WHERE LEVEL>=:minUserlevel ORDER BY NOM,PRENOM";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'minUserlevel' => $minUserlevel
        ]);

        return $stmt->fetchAll();
    }

    public function insert(array $user)
    {
        $date = (new DateTime())->format('Y-m-d');
        $expire_on = ((new DateTime())->add(new DateInterval('P2Y')))->format('Y-m-d');
        $sql = "INSERT INTO " . $this->table_prefix . "_user3 
            (login, hashpass, nom, prenom, email, level, regime, solde, maj_solde, statut, dtcreation, dtexpiration, pt_conso, REM, libre) VALUES 
            (:login, :hashpass, :nom, :prenom, :email, :level, :regime, :solde, :maj_solde, :statut, :dtcreation, :dtexpiration, :pt_conso, :REM, :libre)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':login' => $user['login'],
            ':hashpass' => $user['hashpass'],
            ':nom' => $user['nom'],
            ':prenom' => $user['prenom'],
            ':email' => $user['email'],
            ':level' => $user['level'],
            ':regime' => $user['regime'],
            ':solde' => $user['solde'],
            ':maj_solde' => $user['maj_solde'],
            ':statut' => $user['statut'],
            ':dtcreation' => $date,
            ':dtexpiration' => $expire_on,
            ':pt_conso' => $user['pt_conso'],
            ':REM' => $user['REM'],
            ':libre' => $user['libre']
        ]);
    }

    public function update(array $user)
    {
        $sql = "UPDATE " . $this->table_prefix . "_user3 SET login=:login, hashpass=:hashpass, nom=:nom, prenom=:prenom, 
            email=:email, level=:level, regime=:regime, solde=:solde, maj_solde=:maj_solde, 
            statut=:statut, pt_conso=:pt_conso, REM=:REM, libre=:libre 
            WHERE ID=:ID";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':login' => $user['login'],
            ':hashpass' => $user['hashpass'],
            ':nom' => $user['nom'],
            ':prenom' => $user['prenom'],
            ':email' => $user['email'],
            ':level' => $user['level'],
            ':regime' => $user['regime'],
            ':solde' => $user['solde'],
            ':maj_solde' => $user['maj_solde'],
            ':statut' => $user['statut'],
            ':pt_conso' => $user['pt_conso'],
            ':REM' => $user['REM'],
            ':libre' => $user['libre'],
            ':ID' => $user['ID']
        ]);
    }
}