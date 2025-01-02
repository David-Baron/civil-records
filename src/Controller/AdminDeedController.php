<?php

namespace CivilRecords\Controller;

use CivilRecords\Model\DeedModel;
use CivilRecords\Engine\AbstractController;

class AdminDeedController extends AbstractController
{
    private DeedModel $deedModel;

    public function __construct()
    {
        parent::__construct();
        $this->deedModel = new DeedModel();
    }

    public function list($page = 1)
    {
        $limit = 50;
        $offset = ($page * $limit) - $limit;

        $deeds = $this->deedModel->findAll($limit, $offset);
        return $this->render('next_admin/deed-list.php', [
            'title' => 'Admin users',
            'deeds' => $deeds
        ]);
    }
}
