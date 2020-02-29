<?php

namespace Test;

session_start();

use PHPUnit\Framework\TestCase;
use Controller\{HomeController,FormularioController,Controller};

class FormularioControllerTest extends TestCase
{
    public function testInstanceController()
    {
        $app = new \Slim\App();
        $container = $app->getContainer();
        $container['view'] = function ($container) {
            $view = new \Slim\Views\Twig("." . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'views', [
                'cache' => false
            ]);
            // Instantiate and add Slim specific extension
            $router = $container->get('router');
            $uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
            $view->addExtension(new \Slim\Views\TwigExtension($router, $uri));
            return $view;
        };

        $container['ambiente'] = function($container) {
            $mapStates = parse_ini_file('.' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'state.ini');
            
            $request_uri = 'REQUEST_URI';
            $state_str = 'state';
            $develop_str = 'develop';
            $config_str = 'config';

            if (strpos($_SERVER[$request_uri], $mapStates['homolog']) !== false) {
                $this->container[$state_str] = 'homolog';
            } elseif (strpos($_SERVER[$request_uri], $mapStates[$develop_str]) !== false) {
                $this->container[$state_str] = $develop_str;
            } elseif (strpos($_SERVER[$request_uri], $mapStates['training']) !== false) {
                $this->container[$state_str] = 'training';
            } else {
                $this->container[$state_str] = 'default';
            }

            return $this->container[$state_str];

        };


        $container['state'] = 'default';
        $container['config'] = parse_ini_file("." . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . "config.ini");

        $controller = new FormularioController($container);
        $this->assertTrue(!empty($controller));
    }
}
