<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$request = Request::createFromGlobals();
$response = new Response();
$root = '';
$step = $session->get('step', '1');

$form_errors = [];

switch ($step) {
    case '1':
        require __DIR__ . '/steps/install_1.php';
        break;
    case '2':
        require __DIR__ . '/steps/install_2.php';
        break;
    case '3':
        require __DIR__ . '/steps/install_3.php';
        break;
    case 'last':
        require __DIR__ . '/steps/install_last.php';
        break;
}
