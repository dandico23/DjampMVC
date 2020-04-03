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
        $container['state'] = 'default';

        $controller = new FormularioController($container);
        $this->assertTrue(!empty($controller));
    }
}
