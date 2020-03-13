<?php

namespace Controller;

class ExamplesController extends \Engine\Controller
{

    public function insertMySql($request, $response, $args)
    {
        $examplesModel = $this->loadModel('Examples');
        $insert_values = array("column1" => "value1", "column2" => "value2", "column3" => 4);
        $result = $examplesModel->insertIntoTestTable($insert_values);
        var_dump("Inserted row:");
        var_dump($result);
    }

    public function selectMySql($request, $response, $args)
    {
        $examplesModel = $this->loadModel('Examples');
        $sql = 'SELECT * FROM test_table';
        $result = $examplesModel->select($sql);
        var_dump("Results:");
        var_dump($result);
    }

    public function updateMySql($request, $response, $args)
    {
        $examplesModel = $this->loadModel('Examples');
        $values = array("column1" => "updated");
        $where = array();
        $result = $examplesModel->updateTestTable($values, $where);
        var_dump("Number of updated rows:");
        var_dump($result);
    }

    public function deleteMySql($request, $response, $args)
    {
        $examplesModel = $this->loadModel('Examples');
        $sql = "DELETE FROM test_table WHERE column1 = 'updated'";
        $result = $examplesModel->deleteTestTable($sql);
        var_dump("Number of deleted rows:");
        var_dump($result);
    }

    public function examplePaginate($request, $response, $args)
    {
        # Returns the first page, the total number of pages and the 'cipher_text' and 'iv',
        # which must be used to get the following pages
        $result_array = $examplesModel->paginateFirstPage('test_table', 2);
        var_dump("First page info:");
        var_dump($result_array);

        # Now, use the variables from the previous function to get other pages
        # Get page 2
        $result_array = $examplesModel->paginateOtherPages($result_array['cipher_text'], $result_array['iv'], 2);
        var_dump("Second page info:");
        var_dump($result_array);
    }

    public function exampleValidate($request, $response, $args)
    {
        $examplesModel = $this->loadModel('Examples');
        $examplesModel->test();
    }
}
