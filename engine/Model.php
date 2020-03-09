<?php

namespace Engine;

use lib\PDOHelper;

abstract class Model
{
    protected $databases;
    protected $prefix;
    protected $config;
    protected $env_state;
    protected $container;
    private $error_message;

    public function __construct($state, $config, $container)
    {
        $this->states = parse_ini_file('..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'state.ini', true);
        $this->databases = parse_ini_file('..' . DIRECTORY_SEPARATOR . 'config'
                                               . DIRECTORY_SEPARATOR . 'database.ini', true);
        $this->prefix = $state;
        $this->config = $config;
        $this->container = $container;
        
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

    /**
     * Executa uma requisição GET
     *
     * @param string url
     * @param array parameters (with keys)
     * @param array headers (without keys)
     * @return array [data, response code]
     */
    public function curlGET($url, $parameters, $headers = array())
    {
        if (
            !$url
            || !is_string($url)
            || !preg_match('/((http|https)\:\/\/)?[a-zA-Z0-9\.\/\?\:@\-_=#]+\.([a-zA-Z0-9\&\.\/\?\:@\-_=#])*/', $url)
        ) {
            $this->error_message = "Url inválida";
            $this->handleError(1, $this->error_message);
        }

        # Add get parameters to the url
        $parameters_str = http_build_query($parameters);
        if ($parameters_str) {
            $url .= "?" . $parameters_str;
        }

        $curl  = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return array("data" => $result, "http_code" => $httpcode);
    }

    /**
     * Executa uma requisição GET
     *
     * @param string url
     * @param array data
     * @param array headers (without keys)
     * @return array [data, response code]
     */
    public function curlPOST($url, $data, $headers = array())
    {
        if (
            !$url
            || !is_string($url)
            || !preg_match('/((http|https)\:\/\/)?[a-zA-Z0-9\.\/\?\:@\-_=#]+\.([a-zA-Z0-9\&\.\/\?\:@\-_=#])*/', $url)
        ) {
            $this->error_message = "Url inválida";
            $this->handleError(1, $this->error_message);
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return array("data" => $result, "http_code" => $httpcode);
    }

    /**
     * Retorna somente os elementos permitidos em uma lista
     *
     * @param array $my_array - lista a ser filtrada
     * @param array $allowed - chaves permitidas
     * @return array - lista filtrada
     */
    public function filterAllowedArrayKeys($my_array, $allowed)
    {
        $filtered = array_filter(
            $my_array,
            function ($key) use ($allowed) {
                return in_array($key, $allowed);
            },
            ARRAY_FILTER_USE_KEY
        );
        return $filtered;
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
            $this->handleError(1, $this->error_message);
        }
        
        $this->handleError(1, $this->error_message);
    }

    public function setDate($dados, $key)
    {
        $this->error_message = 'Formato de data inválido';
        if (!isset($dados[$key]) || !is_string($dados[$key])) {
            $this->handleError(1, $this->error_message);
        }
        return $this->dateValidator($dados[$key], false);
    }

    public function setTimeStamp($dados, $key)
    {
        $this->error_message = 'Formato de data inválido';
        if (!isset($dados[$key]) || !is_int($dados[$key])) {
            $this->handleError(1, $this->error_message);
        }

        return $this->dateValidator($dados[$key], true);
    }

    /**
     * Lida com os erros, printando somente caso não esteja em ambiente de produção
     *
     * @param integer $error_class - tipo do erro
     * Valores aceitos:
     *      1 - UnexpectedValueException
     * @param string $message - message to be printed
     * @return
     */
    public function handleError($error_class, $message)
    {
        if ($this->env_state == 'default') {
            if ($error_class == 1) {
                throw new \UnexpectedValueException($message);
            } else {
                throw new \Exception($message);
            }
        }
    }

    public function initDatabase($db)
    {
        if (!isset($this->container[$db])) {
            $this->container[$db] = (object) $this->openConnect($db);
        }
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
