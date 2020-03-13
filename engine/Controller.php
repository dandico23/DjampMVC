<?php

namespace Engine;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Container\ContainerInterface;

use lib\Validator;

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

        $config_str = 'config';
        $this->container[$config_str] = parse_ini_file('..' . DIRECTORY_SEPARATOR . 'config'
                                                        . DIRECTORY_SEPARATOR . 'config.ini', true);
        $this->setEnvironment();

        $this->view = $this->container->get('view');
        $this->flash = $this->container->get('flash');
        $this->state = $this->container->get('state');
        $this->config = $this->container->get('config');
        $this->container->validator = new Validator();
    }

    public function setEnvironment()
    {
        //Registra contanier com o ambiente atual
        if (empty($this->container['ambiente'])) {
            $mapStates = parse_ini_file('..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'state.ini');
            
            $request_uri = 'REQUEST_URI';
            $state_str = 'state';
            $develop_str = 'develop';

            if (strpos($_SERVER[$request_uri], $mapStates['homolog']) !== false) {
                $this->container[$state_str] = 'homolog';
            } elseif (strpos($_SERVER[$request_uri], $mapStates[$develop_str]) !== false) {
                $this->container[$state_str] = $develop_str;
            } elseif (strpos($_SERVER[$request_uri], $mapStates['training']) !== false) {
                $this->container[$state_str] = $develop_str;
            } else {
                $this->container[$state_str] = 'default';
            }
        }
    }

    public function loadModel($model)
    {
        $class = '\\models\\' . $model . 'Model';
        return new $class($this->state, $this->config, $this->container);
    }
}
