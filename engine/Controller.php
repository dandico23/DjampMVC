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
        
        $this->container = $container;
        $this->view = $this->container->get('view');
        $this->state = $this->container->get('state');
        $this->config = $this->container->get('config');
        $this->validator = new Validator();
    }

    public function loadModel($model)
    {
        $class = '\\models\\' . $model . 'Model';
        return new $class($this->state, $this->config, $this->container);
    }
}
