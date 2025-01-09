<?php

namespace CivilRecords\Controller;

use CivilRecords\Domain\UserModel;
use CivilRecords\Engine\Validator;
use CivilRecords\Engine\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AdminUserController extends AbstractController
{
    private UserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
    }

    public function list(Request $request)
    {
        $limit = 50;
        $offset = ($request->get('page', 1) * $limit) - $limit;

        $users = $this->userModel->findAll($limit, $offset);
        return $this->render('next_admin/user-list.php', [
            'title' => 'Admin users',
            'users' => $users
        ]);
    }

    public function edit(Request $request)
    {
        if ($request->get('id', null) === null) {
            $response = new RedirectResponse("/admin/utilisateurs");
            $response->send();
            exit();
        }

        $userModel = new UserModel();
        $user = $userModel->findId($request->get('id'));

        if ($request->getMethod() === 'POST') {
            $user = $request->request->all();
            $validator = new Validator();
            if ($validator->validate($request->request->all(), 'user') && $validator->isValid()) {
                // Store

                // Redirect success
            }

            $form_errors = $validator->getErrors();
        }

        return $this->render('next_admin/user-form.php', [
            'title' => 'User',
            'user' => $user
        ]);
    }
}
