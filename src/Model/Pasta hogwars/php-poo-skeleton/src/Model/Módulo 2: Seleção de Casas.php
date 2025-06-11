<?php

require_once '../vendor/autoload.php';

use Hogwarts\Services\SelecaoService;
use Hogwarts\Controllers\SelecaoController;

$selecaoService = new SelecaoService();
$controller = new SelecaoController($selecaoService);

$acao = $_GET['acao'] ?? 'home';

switch ($acao) {
    case 'questionario':
        $controller->mostrarQuestionario();
        break;
    case 'processar':
        $controller->processarSelecao();
        break;
    case 'casas':
        $controller->mostrarCasas();
        break;
    default:
        echo "<h1>Seleção de Casas de Hogwarts</h1>";
        echo "<p><a href='?acao=questionario'>Fazer Seleção</a></p>";
        echo "<p><a href='?acao=casas'>Ver Casas</a></p>";
}

$selecaoService = new SelecaoService();

$respostas = [
    ['pergunta' => 0, 'opcao' => 'A'],  // Coragem
    ['pergunta' => 1, 'opcao' => 'A'],  // Aventuras  
    ['pergunta' => 2, 'opcao' => 'A']   // Herói
];

$resultado = $selecaoService->realizarSelecao(123, $respostas);
echo "Aluno foi selecionado para: " . $resultado['casa'];
