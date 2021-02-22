<?php

namespace Engine;

use lib\PDOHelper;
use lib\Validator;
use lib\CustomException;

abstract class Model
{
    protected $databases;
    protected $config;
    protected $env_state;
    protected $container;
    protected $validator;
    private $error_message;
    protected $dir;
    protected $config_dir;

    public function __construct()
    {
        $this->dir = str_replace("engine", "", __DIR__);
        $this->config_dir = $this->dir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
        $this->databases = parse_ini_file($this->config_dir . 'database.ini', true);
        $this->config = parse_ini_file($this->config_dir . 'config.ini', true);
        $this->container = array();
        $this->validator = new Validator();
        $this->setEnvState();
    }

    public function setEnvState()
    {
        $this->states = parse_ini_file($this->dir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'state.ini', true);
        $request_uri = 'REQUEST_URI';
        $develop_str = 'develop';
        if (strpos($_SERVER[$request_uri], $this->states['homolog']) !== false) {
            $urlEnvParameter = 'homolog';
        } elseif (strpos($_SERVER[$request_uri], $this->states[$develop_str]) !== false) {
            $urlEnvParameter = $develop_str;
        } elseif (strpos($_SERVER[$request_uri], $this->states['training']) !== false) {
            $urlEnvParameter = $develop_str;
        } else {
            $urlEnvParameter = 'default';
        }
        $this->env_state = array_search($urlEnvParameter, $this->states);
        if (!$this->env_state) {
            $this->env_state = $urlEnvParameter; # Expected to be default
        }
    }

    public function getServerApi()
    {
        $availableApis = parse_ini_file($this->dir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'api.ini', true);
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
     * @return array [data, http_code, header_size, result]
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

        // Checa se há a necessidade de adição de queries
        if (isset($parameters) && !empty($parameters)) {
            # Add get parameters to the url
            $parameters_str = http_build_query($parameters);
            if ($parameters_str) {
                $url .= "?" . $parameters_str;
            }
        }

        $curl  = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        $result = curl_exec($curl);

        // Busca dados da requisição curl
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $body = substr($result, $header_size);
        
        curl_close($curl);

        return array("data" => $body, "http_code" => $httpcode, "header_size" => $header_size, 'result' => $result);
    }

    /**
     * Executa uma requisição POST
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

    // Cria array de bindings através de um array associativo ($chave => $valor)
    public function createBindingsArray($parameters)
    {
        $bindingsArray = array();
        foreach ($parameters as $key => $value) {
            $bindingsArray[':' . $key] = $this->setValor($parameters, $key);
        }

        return $bindingsArray;
    }

    public function setValor($dados, $key)
    {
        if (isset($dados[$key]) && $dados[$key] === false) {
            $dados[$key] = "false";
            return $dados[$key];
        }
        return isset($dados[$key]) ? $dados[$key] : null;
    }

    public function setValorCheckbox($dados, $key)
    {
        return isset($dados[$key]) && $dados[$key] != '' ? $dados[$key] : 'false';
    }

    public function returnDate($date)
    {
        $data = array('date' => $date);
        $rules = array('date' => 'date_format:Y-m-d');
        if (!$this->validator->validate($data, $rules)['valid']) {
            $this->handleError(1, $this->error_message);
        } else {
            $dt = new \DateTime();
            $date = $dt->createFromFormat('Y-m-d', $date);
            return $date->format('Y-m-d');
        }
    }

    public function setDate($dados, $key)
    {
        $this->error_message = 'Formato de data inválido';
        if (!isset($dados[$key]) || !is_string($dados[$key])) {
            $this->handleError(1, $this->error_message);
        }
        return $this->returnDate($dados[$key]);
    }

    public function setTimeStamp($dados, $key)
    {
        $this->error_message = 'Formato de data inválido';
        if (!isset($dados[$key]) || !is_int($dados[$key])) {
            $this->handleError(1, $this->error_message);
        }
        return $this->returnDate($dados[$key]);
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

    public function begin($db)
    {
        $this->container[$db]->beginTransaction();
    }
    public function commit($db)
    {
        $this->container[$db]->commit();
    }
    public function rollBack($db)
    {
        $this->container[$db]->rollBack();
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

    /**
     * Retorna o IP do Cliente.
     *
     * @return void
     */
    public function getIP()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }
    
    /**
     * Formata um array para se adequar a clausula IN
     *
     * @param mixed $arr
     *
     * @return [type]
     */
    public function createInClause($arr)
    {
        return '\'' . implode('\', \'', $arr) . '\'';
    }
}
