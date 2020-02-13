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

//Registra contanier com o ambiente atual
if (empty($container['ambiente'])) {
    $mapStates = parse_ini_file('..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'state.ini');

    $request_uri = 'REQUEST_URI';
    $state_str = 'state';
    $develop_str = 'develop';

    if (strpos($_SERVER[$request_uri], $mapStates['homolog']) !== false) {
        $container[$state_str] = 'homolog';
    } elseif (strpos($_SERVER[$request_uri], $mapStates[$develop_str]) !== false) {
        $container[$state_str] = $develop_str;
    } elseif (strpos($_SERVER[$request_uri], $mapStates['training']) !== false) {
        $container[$state_str] = $develop_str;
    } else {
        $container[$state_str] = 'default';
    }
}

require('..' . DIRECTORY_SEPARATOR . 'App' .  DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'route.php');

// Run app
$app->run();
