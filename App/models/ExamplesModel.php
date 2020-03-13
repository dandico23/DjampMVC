<?php

namespace models;

class ExamplesModel extends \Engine\Model
{

    public function __construct($state, $config, $container)
    {
        parent::__construct($state, $config, $container);
        //aqui deve se iniciar a conexão com todos os bds que a model se conectará
        $this->initDatabase("mysql_test");
    }

    public function insertIntoTestTable($values)
    {
        return $this->container["mysql_teste"]->insert('test_table', $values);
    }

    public function updateTestTable($values, $where)
    {
        return $this->container["mysql_teste"]->update('test_table', $values, $where);
    }

    public function deleteTestTable($sql)
    {
        return $this->container["mysql_teste"]->delete($sql);
    }

    public function select($sql)
    {
        return $this->container["mysql_teste"]->select($sql);
    }

    public function paginateFirstPage($table, $limit)
    {
        return $this->container["mysql_teste"]->paginateGetTotalPages($table, $limit);
    }

    public function paginateOtherPages($cipherText, $iv, $page)
    {
        return $this->container["mysql_teste"]->selectPaginate($cipherText, $iv, $page);
    }

    public function validateExample()
    {
        $data = [
            'email' => 'daniel@mail.com',
            'age' => 2,
            'expire_date' => 'tomorrow',
            'punctuation' => 10,
            'color' => 'blue',
            'phone' => '34358525'
        ];
        $rules = [
            'email' => 'required|email',
            'age' => 'required|numeric',
            'expire_date' => 'date|after:now',
            'punctuation' => 'numeric|between:1,11|different:4,5',
            'color' => 'string|in:red,green,blue',
            'phone' => 'size:8'
        ];

        $valid_result = $this->validator($data, $rules);
        var_dump($valid_result);
    }

}
