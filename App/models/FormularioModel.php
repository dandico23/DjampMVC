<?php

namespace models;

class FormularioModel extends Model
{

    public function __construct($state)
    {
        parent::__construct($state);

        //aqui deve se iniciar a conexão com todos os bds que a model se conectará
        $this->db->regatas = (object) $this->openConnect("regatas");
    }

    public function hello()
    {
        var_dump($this->databases);
    }
}
