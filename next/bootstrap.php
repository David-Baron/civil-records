<?php


if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/ressources/message_vendor_error.html';
    exit();
}

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

$session = new Session();
$session->start();

if (!file_exists(__DIR__ . '/../.env.local.php') || file_exists(__DIR__ . '/../setup')) {
    require(__DIR__ . "/../setup/install.php");
    exit();
}

$_ENV = require(__DIR__ . '/../.env.local.php');
$root = $_ENV['APP_ROOT'];

require(__DIR__ . '/Engine/AppConfiguration.php');
$config = new AppConfiguration();
$lg = 'fr';

if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'dev') {

    error_reporting(E_ALL);

    function dd(mixed $data)
    {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
        exit();
    }
}

require(__DIR__ . '/Engine/UserAuthorizer.php');

$request = Request::createFromGlobals();
$response = new Response();

$userAuthorizer = new UserAuthorizer($session);
