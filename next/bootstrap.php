<?php

error_reporting(E_ALL);

function dd(mixed $data)
{
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    exit();
}

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$request = Request::createFromGlobals();
$response = new Response();

$root = "";