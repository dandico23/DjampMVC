<?php

use Controller\{FormularioController,AuthController,LogController};


$app->group('', function ($app) {
    $app->get('/', FormularioController::class . ':getFormulario');
    #$app->get('/formulario/{name}', FormularioController::class . ':showFormulario');
    #$app->post('/formulario/{name}', FormularioController::class . ':postDadosFormulario');
    #$app->get('/formulario', FormularioController::class . ':getCadastro');
    $app->get('/formulario', FormularioController::class . ':getCadastro');
})->add(AuthController::class . ':authWithCookie');

#Middleware de Log deve ser adicionada antes da Middleware de Auth
