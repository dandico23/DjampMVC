<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

//sessão é obrigatória para mensagens flash
session_start();
require("../vendor/autoload.php");

$configuration = [
    'settings' => [
        'displayErrorDetails' => true,
    ],
];
$c = new \Slim\Container($configuration);
// Create app
$app = new \Slim\App($c);

// Cria um container
$container = $app->getContainer();

require('..' . DIRECTORY_SEPARATOR . 'App' .  DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'route.php');

// Run app
$app->run();
