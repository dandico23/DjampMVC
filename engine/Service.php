<?php

namespace Engine;

abstract class Service
{
    protected $state;
    protected $config;
    protected $validator;
    protected $container;

    public function __construct()
    {
        $this->dir = str_replace("engine", "", __DIR__);
        $this->validator = new \lib\Validator();
    }

    public function loadModel($model)
    {
        $class = '\\models\\' . $model . 'Model';
        return new $class();
    }
}
