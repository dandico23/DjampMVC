<?php

use Controller\{FormularioController, AuthController, LogController, ExamplesController};


$app->group('', function ($app) {
    $app->get('/', FormularioController::class . ':showFormulario');
    $app->post('/formulario', FormularioController::class . ':postDadosFormulario');
    $app->get('/formulario', FormularioController::class . ':showFormulario');
    $app->get('/examples/insert', ExamplesController::class . ':insertMySql');
    $app->get('/examples/select', ExamplesController::class . ':selectMySql');
    $app->get('/examples/update', ExamplesController::class . ':updateMySql');
    $app->get('/examples/delete', ExamplesController::class . ':deleteMySql');
    $app->get('/examples/paginate', ExamplesController::class . ':examplePaginate');
    $app->get('/examples/validate', ExamplesController::class . ':exampleValidate');
    
});

#TODO Middleware de Log deve ser adicionada antes da Middleware de Auth
