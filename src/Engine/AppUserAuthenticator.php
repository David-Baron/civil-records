<?php

namespace CivilRecords\Engine;

use CivilRecords\Domain\UserModel;
use Symfony\Component\HttpFoundation\Session\Session;

class AppUserAuthenticator
{
    private Session $session;
    private UserModel $userModel;

    public function __construct(Session $session)
    {
        $this->session = $session;
        $this->userModel = new UserModel();
    }

    public function authenticate(string $identifier, string $plainTextPassword)
    {
        $user = $this->userModel->findOneByCriteria(['login' => $identifier]);
        if ($user && sha1($plainTextPassword) === $user['hashpass']) { // pass hashed with old sh1
            // OK set in session
            $this->session->set('user', [
                'login' => $user['login'],
                'hashpass' => $user['hashpass'],
                'nom' => $user['nom'],
                'prenom' => $user['prenom'],
                'email' => $user['email'],
                'level' => $user['level'],
                'regime' => $user['regime'],
                'solde' => $user['solde'],
                'maj_solde' => $user['maj_solde'],
                'statut' => $user['statut'],
                ':dtcreation' => $user['dtcreation'],
                ':dtexpiration' => $user['expire_on'],
                'pt_conso' => $user['pt_conso'],
                'REM' => $user['REM'],
                'ID' => $user['ID']
            ]);
            // $passwordWithnewEncoding = password_hash($plainTextPassword, PASSWORD_DEFAULT); TODO: database need to be modified (varchar 40 actualy, 255 needed)
            return true;
        }/*  elseif ($user && password_verify($plainTextPassword, $user['hashpass'])) { // pass hashed with password_hash
            // OK set in session
            return true;
        } */

        $this->session->set('antiflood', $this->session->get('antiflood', 0) + 1);
        return false;
    }
}
