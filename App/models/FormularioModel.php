<?php

namespace models;

class FormularioModel extends \Engine\Model
{

    public function __construct($state, $config, $container)
    {
        parent::__construct($state, $config, $container);
        //aqui deve se iniciar a conexão com todos os bds que a model se conectará
        $this->initDatabase("teste");
    }

    public function getAtividades()
    {
        return $this->container["teste"]->select('select * from tabela_teste');
    }
}
