<?php

namespace Controller;

class FormularioController extends \Engine\Controller
{


    public function showFormulario($request, $response, $args)
    {
        //instancia uma model
        //abre uma conexão com o bd desejado - fazer implementação
        return $this->view->render($response, 'formulario/formulario.html');
    }

    //TODO - Melhorar exemplo de demonstração com a conexão do banco
    public function postDadosFormulario($request, $response, $args)
    {
        $dadosForm = $request->getParsedBody();
        $atividadesModel = $this->loadModel('Formulario');
        $Atividades = $atividadesModel->getAtividades();
        return $this->view->render($response, 'formulario/sucesso.html', $dadosForm);
    }

}
