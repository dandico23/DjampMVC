<?php

use Controller\{FormularioController,AuthController,LogController};


$app->group('', function ($app) {
    $app->get('/', FormularioController::class . ':showFormulario');
    $app->post('/formulario', FormularioController::class . ':postDadosFormulario');
    $app->get('/formulario', FormularioController::class . ':showFormulario');
});

#TODO Middleware de Log deve ser adicionada antes da Middleware de Auth
