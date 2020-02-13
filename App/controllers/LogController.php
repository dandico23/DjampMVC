<?php

namespace Controller;

class LogController extends Controller
{
    public function getLog($request, $response, $next)
    {
        $logModel = $this->loadModel('Log');
        
        $dados = array("method" => $request->getMethod());
        $dados['route'] = $request->getAttribute('route')->getPattern();
        $dados['ip'] = $request->getAttribute('ip_address');
        $dados['date'] = date("Y-m-d H:i:s");
        $dados['user'] = $_COOKIE['authsagi_cpf'];
        $dados['status'] = $next($request, $response)->getStatusCode();

        $insertResponse = $logModel->insertLog($dados);
        
        return $response;
    }
}
