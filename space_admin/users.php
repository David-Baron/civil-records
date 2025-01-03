<?php 

use CivilRecords\Controller\AdminUserController;

$adminUserController = new AdminUserController();
$content = $adminUserController->list();

$response->setContent($content);
$response->send(true);
exit();