<?php

namespace models;

class LogModel extends \Engine\Model
{

    public function __construct($state, $config, $container)
    {
        parent::__construct($state, $config, $container);
    }

    public function insertLog($dados)
    {
        $values = array('method' => $this->setValor($dados, 'method'));
        $values['route'] = $this->setValor($dados, 'route');
        $values['ip'] = $this->setValor($dados, 'ip');
        $values['date'] = $this->setValor($dados, 'date');
        $values['user'] = $this->setValor($dados, 'user');
        $values['status'] = $this->setValor($dados, 'status');

        return $this->db->mysql->insert('log', $values);
    }
}
