<?php

use Core\Router;

$router = new Router();

// Formato: $router->get('/rota', 'Controller@metodo', 'Middleware@metodo' /* opcional */);

$router->get('/', 'CadastroController@index');
$router->get('/cadastro', 'CadastroController@index');

$router->get('/cadastro/api/estudantes', 'CadastroController@estudantes');
$router->get('/cadastro/api/cursos', 'CadastroController@cursos');
$router->get('/cadastro/api/matriculas', 'CadastroController@matriculas');
$router->post('/cadastro/api/estudantes/salvar', 'CadastroController@salvarEstudante');
$router->post('/cadastro/api/cursos/salvar', 'CadastroController@salvarCurso');
$router->post('/cadastro/api/matriculas/salvar', 'CadastroController@matricular');
$router->post('/cadastro/api/excluir', 'CadastroController@excluir');

return $router;
