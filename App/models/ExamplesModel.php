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

    public function test()
    {
        $this->container->validator->validate(1,2);
    }

}
