<?php

namespace Djamp;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class App
{
    private $app;
    private $dir;

    public function __construct()
    {
        //sessão é obrigatória para mensagens flash
        if (!isset($_SESSION)) {
            session_start();
        }

        $this->dir = str_replace("public", "", __DIR__);
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

        // Registra o componente View no Container
        if (empty($container['view'])) {
            $container['view'] = function ($container) {
                $view = new \Slim\Views\Twig($this->dir . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . "views", [
                    'cache' => false
                ]);
                // Instantiate and add Slim specific extension
                $router = $container->get('router');
                $uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
                $view->addExtension(new \Slim\Views\TwigExtension($router, $uri));
                $view->getEnvironment()->addGlobal("session", $_SESSION);
                return $view;
            };
        }
        //Registra o componente Flash
        if (empty($container['flash'])) {
            $container['flash'] = function () {
                return new \Slim\Flash\Messages();
            };
        }
        $config_str = 'config';
        $container[$config_str] = parse_ini_file($this->dir . DIRECTORY_SEPARATOR . 'config'
        . DIRECTORY_SEPARATOR . 'config.ini', true);


        //Registra contanier com o ambiente atual
        if (empty($container['ambiente'])) {
            $mapStates = parse_ini_file($this->dir . DIRECTORY_SEPARATOR .  'config' . DIRECTORY_SEPARATOR . 'state.ini');
            $config = parse_ini_file($this->dir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.ini');

            $request_uri = 'REQUEST_URI';
            $state_str = 'state';
            $develop_str = 'develop';
            $config_str = 'config';
            if (strpos($_SERVER[$request_uri], $mapStates['homolog']) !== false) {
                $container[$state_str] = 'homolog';
            } elseif (strpos($_SERVER[$request_uri], $mapStates[$develop_str]) !== false) {
                $container[$state_str] = $develop_str;
            } elseif (strpos($_SERVER[$request_uri], $mapStates['training']) !== false) {
                $container[$state_str] = $develop_str;
            } else {
                $container[$state_str] = 'default';
            }
            $container[$config_str] = $config;
        }

        require($this->dir . DIRECTORY_SEPARATOR . 'App' .  DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'route.php');

        $this->app = $app;
    }

    public function get()
    {
        return $this->app;
    }
}


