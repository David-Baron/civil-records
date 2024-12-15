<?php

require(__DIR__ . '/../Engine/DatabaseConnection.php');

class UserModel extends DatabaseConnection
{
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
}
