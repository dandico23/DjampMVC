<?php

namespace models;

class ExempleService extends \Engine\Service
{
    public function insertMySql($body)
    {
        $rules = array (
            'id' => 'required' . '|' . 'in:1,2',
            'name' => REQUIRED
        );

        $this->validator->validate($body, $rules);

        $examplesModel = $this->loadModel('Examples');
        $insert_values = array("column1" => $body['id'], "column2" => $body['name']);
        $result = $examplesModel->insertIntoTestTable($insert_values);

        return $result;
    }
}
