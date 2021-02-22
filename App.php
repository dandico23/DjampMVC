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
            if (!isset($_SESSION['ghost'])) {
                $_SESSION['ghost'] = rand(0, 50000);
            }
        }

        $this->dir = str_replace("public", "", __DIR__);
        $configuration = [
            'settings' => [
                'displayErrorDetails' => true,
                'determineRouteBeforeAppMiddleware' => true
            ],
        ];
        $c = new \Slim\Container($configuration);
        // Create app
        $app = new \Slim\App($c);

        // Cria um container
        $container = $app->getContainer();
        // configurações
        $config = parse_ini_file($this->dir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.ini');
        $container['config'] = $config;

        // Registra o componente View no Container
        if (empty($container['view'])) {
            $container['view'] = function ($container) {
                $view = new \Slim\Views\Twig($this->dir . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . "views", [
                    'cache' => false
                ]);
                // Instantiate and add Slim specific extension
                $router = $container->get('router');
                $serverInfo = $_SERVER;
                $uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($serverInfo));
                $view->addExtension(new \Slim\Views\TwigExtension($router, $uri));
                $version = json_decode(file_get_contents($this->dir . DIRECTORY_SEPARATOR . 'composer.json'));
                $view->getEnvironment()->addGlobal("session", $_SESSION);
                $view->getEnvironment()->addGlobal("version", $version->version);
                
                return $view;
            };
        }
        //Registra o componente Flash
        if (empty($container['flash'])) {
            $container['flash'] = function () {
                return new \Slim\Flash\Messages();
            };
        }

        require($this->dir . DIRECTORY_SEPARATOR . 'App' .  DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'route.php');

        $this->app = $app;
    }

    public function get()
    {
        return $this->app;
    }
}
