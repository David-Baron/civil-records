<?php 

use Symfony\Component\HttpFoundation\Session\Session;

class UserAuthorizer
{
    private Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function isAuthenticated()
    {
        return $this->session->get('user') ? true : false;
    }

    public function isGranted(int $level)
    {
        if ($this->isAuthenticated() && $this->session->get('user')['level'] >= $level) {
            return true;
        }

        return false;
    }
}