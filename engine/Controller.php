<?php

namespace Engine;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Container\ContainerInterface;

abstract class Controller
{
    protected $container;
    protected $view;
    protected $flash;
    protected $state;
    protected $config;

    public function __construct(ContainerInterface $container)
    {
        // Registra o componente View no Container
        if (empty($container['view'])) {
            $container['view'] = function ($container) {
                $view = new \Slim\Views\Twig(".." . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . "views", [
                    'cache' => false
                ]);
                // Instantiate and add Slim specific extension
                $router = $container->get('router');
                $uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
                $view->addExtension(new \Slim\Views\TwigExtension($router, $uri));
                return $view;
            };
        }
        //Registra o componente Flash
        if (empty($container['flash'])) {
            $container['flash'] = function () {
                return new \Slim\Flash\Messages();
            };
        }

        $this->container = $container;

        $this->view = $this->container->get('view');

        $this->flash = $this->container->get('flash');

        $this->state = $this->container->get('state');

        $this->config = $this->container->get('config');
    }

    public function loadModel($model)
    {
        $class = '\\models\\' . $model . 'Model';
        return new $class($this->state, $this->config);
    }
}
