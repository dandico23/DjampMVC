<?php

namespace models;

class FormularioModel extends \Engine\Model
{

    public function __construct($state, $config)
    {
        parent::__construct($state, $config);
        //aqui deve se iniciar a conexão com todos os bds que a model se conectará
        $this->db->atividades = (object) $this->openConnect("teste");
    }

    public function getAtividades()
    {
        return $this->db->atividades->select('select * from tabela_teste');
    }
}
