<?php

namespace CivilRecords\Controller;

use CivilRecords\Model\UserModel;
use CivilRecords\Engine\AbstractController;

class AdminUserController extends AbstractController
{
    private UserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
    }

    public function list($page = 1)
    {
        $limit = 50;
        $offset = ($page * $limit) - $limit;

        $users = $this->userModel->findAll($limit, $offset);
        return $this->render('next_admin/user-list.php', [
            'title' => 'Admin users',
            'users' => $users
        ]);
    }
}
