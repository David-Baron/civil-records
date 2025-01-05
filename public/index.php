<?php

require __DIR__ . '/../src/bootstrap.php';

$maped_routes = require(__DIR__ . '/../src/ressources/mapped_routes.php');

$path = $request->getPathInfo();

if (isset($maped_routes[$path])) {
    $view = include __DIR__ . '/..' .$maped_routes[$path];
    $response->setContent($view);
} else {
    $response->setStatusCode(404);
    $response->setContent('Not Found');
}

$response->send();
