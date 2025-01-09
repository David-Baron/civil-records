<?php

namespace CivilRecords\Controller;

use CivilRecords\Engine\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class AdminDeedController extends AbstractController
{

    public function __construct(Request $request)
    {
        parent::__construct();

    }

    public function list(Request $request)
    {
        $limit = 50;
        $offset = ($request->get('page', 1) * $limit) - $limit;

        // $deeds = $this->deedModel->findAll($limit, $offset);
        return $this->render('next_admin/deed-list.php', [
            'title' => 'Admin users',
            // 'deeds' => $deeds
        ]);
    }
}
