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
    protected $validator;
    
    public function __construct(ContainerInterface $container)
    {
        $this->dir = str_replace("engine", "", __DIR__);
        $this->container = $container;
        $this->view = $this->container->get('view');
        $this->config = $this->container->get('config');
        $this->validator = new Validator();
    }

    public function loadModel($model)
    {
        $class = '\\models\\' . $model . 'Model';
        return new $class();
    }

    public function loadService($service)
    {
        $class = '\\services\\' . $service . 'Service';
        return new $class($this->container);
    }
}
