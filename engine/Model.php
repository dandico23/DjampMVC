<?php

namespace Engine;

use lib\PDOHelper;

abstract class Model
{
    protected $databases;
    protected $prefix;
    protected $config;
    protected $env_state;
    public $db;
    private $error_message;

    public function __construct($state, $config)
    {
        $this->db = new \stdClass();
        $this->states = parse_ini_file('..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'state.ini', true);
        $this->databases = parse_ini_file('..' . DIRECTORY_SEPARATOR . 'config'
                                               . DIRECTORY_SEPARATOR . 'database.ini', true);
        $this->prefix = $state;
        $this->config = $config;

        $this->env_state = array_search($this->prefix, $this->states);
        if (!$this->env_state) {
            $this->env_state = $this->prefix; # Expected to be default
        }
    }

    public function getServerApi()
    {
        $availableApis = parse_ini_file('..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'api.ini', true);
        $apiInfo = $availableApis[$this->env_state];
        $api = $apiInfo['host'];
        if ($apiInfo['port']) {
            $api .= ':' . $apiInfo['port'];
        }
        $to_return = array('api' => $api, 'token' => $apiInfo['token']);
        $to_return['Content-Type'] = $apiInfo['Content-Type'];
        $to_return['Cache-Control'] = $apiInfo['Cache-Control'];
        return $to_return;
    }

    public function setValor($dados, $key)
    {
        return (isset($dados[$key]) && $dados[$key]) ? $dados[$key] : null;
    }

    public function setValorCheckbox($dados, $key)
    {
        return isset($dados[$key]) && $dados[$key] != '' ? $dados[$key] : 'false';
    }

    public function dateValidator($dte, $timestamp)
    {
        if ($dte == '') {
            return $dte;
        }

        $dt = new \DateTime();
        try {
            if ($timestamp) {
                $date->setTimestamp($dte);
            } else {
                $date = $dt->createFromFormat('Y-m-d', $dte);
            }
            return $date->format('Y-m-d');
        } catch (\ErrorException $e) {
            throw new \UnexpectedValueException($this->error_message);
        }
        
        throw new \UnexpectedValueException($this->error_message);
    }

    public function setDate($dados, $key)
    {
        $this->error_message = 'Formato de data inválido';
        if (!isset($dados[$key]) || !is_string($dados[$key])) {
            throw new \UnexpectedValueException($this->error_message);
        }
        return $this->dateValidator($dados[$key], false);
    }

    public function setTimeStamp($dados, $key)
    {
        $this->error_message = 'Formato de data inválido';
        if (!isset($dados[$key]) || !is_int($dados[$key])) {
            throw new \UnexpectedValueException($this->error_message);
        }

        return $this->dateValidator($dados[$key], true);
    }

    public function openConnect($database)
    {
        $db_data = $this->databases[$this->env_state . '_' . $database];

        $dsn = $db_data['type'] . ':host=' . $db_data['host'] . ';port=' . $db_data['port'];
        $dsn .= ';dbname=' . $db_data['dbname'];
        $user = $db_data['user'];
        $pass = $db_data['password'];

        return new PDOHelper($dsn, $user, $pass, $this->env_state, $db_data['type'], $this->config, []);
    }
}
