<?php

namespace Controller;

class FormularioController extends Controller
{


    public function getFormulario($request, $response, $args)
    {
        return $this->view->render($response, 'formulario/cadastroUsuario.html');
    }

    public function showFormulario($request, $response, $args)
    {
        //instancia uma model
        $teste = $this->loadModel('Formulario');
        //abre uma conexão com o bd desejado - fazer implementação
        return $this->view->render($response, 'formulario/formulario.html');
    }

    
    public function postDadosFormulario($request, $response, $args)
    {
        $dadosForm = $request->getParsedBody();
        
        return $this->view->render($response, 'formulario/sucesso.html', $dadosForm);
    }

}
