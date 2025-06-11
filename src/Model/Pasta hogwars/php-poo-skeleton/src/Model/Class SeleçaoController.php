<?php

namespace Hogwarts\Controllers;

use Hogwarts\Services\SelecaoService;

class SelecaoController
{
    private $selecaoService;

    public function __construct(SelecaoService $selecaoService)
    {
        $this->selecaoService = $selecaoService;
    }

    public function mostrarQuestionario()
    {
        $alunoId = $_GET['aluno'] ?? 1;
        $perguntas = $this->selecaoService->getPerguntas();

        echo "<h2>Chapéu Seletor - Descubra sua Casa!</h2>";
        echo "<form method='POST' action='?acao=processar'>";
        echo "<input type='hidden' name='alunoId' value='{$alunoId}'>";

        foreach ($perguntas as $index => $pergunta) {
            echo "<div style='margin: 20px 0; padding: 10px; border: 1px solid #ccc;'>";
            echo "<h3>" . ($index + 1) . ". " . $pergunta['pergunta'] . "</h3>";
            
            foreach ($pergunta['opcoes'] as $letra => $opcao) {
                echo "<label style='display: block; margin: 5px 0;'>";
                echo "<input type='radio' name='pergunta_{$index}' value='{$letra}' required> ";
                echo $opcao['texto'];
                echo "</label>";
            }
            echo "</div>";
        }

        echo "<button type='submit'>Descobrir Minha Casa!</button>";
        echo "</form>";
    }

    public function processarSelecao()
    {
        $alunoId = $_POST['alunoId'];
        $respostas = [];

        foreach ($_POST as $key => $value) {
            if (strpos($key, 'pergunta_') === 0) {
                $numeroPergunta = str_replace('pergunta_', '', $key);
                $respostas[] = [
                    'pergunta' => (int)$numeroPergunta,
                    'opcao' => $value
                ];
            }
        }

        try {
            $resultado = $this->selecaoService->realizarSelecao($alunoId, $respostas);
            $this->mostrarResultado($resultado);
        } catch (Exception $e) {
            echo "Erro: " . $e->getMessage();
        }
    }

    public function mostrarCasas()
    {
        $casas = $this->selecaoService->getCasas();

        echo "<h2>As Casas de Hogwarts</h2>";
        
        foreach ($casas as $casa) {
            echo "<div style='margin: 15px; padding: 15px; border: 2px solid #ddd;'>";
            echo "<h3>" . $casa->getNome() . "</h3>";
            echo "<p>" . $casa->getDescricao() . "</p>";
            echo "<p><strong>Valores:</strong> " . implode(', ', $casa->getValores()) . "</p>";
            echo "<p><strong>Alunos:</strong> " . $casa->getTotalAlunos() . "</p>";
            echo "</div>";
        }
    }

    private function mostrarResultado($resultado)
    {
        $casas = $this->selecaoService->getCasas();
        $casa = $casas[$resultado['casa']];

        echo "<div style='text-align: center; padding: 30px; background: #f0f8ff;'>";
        echo "<h1>🎉 RESULTADO 🎉</h1>";
        echo "<h2>Você pertence à casa:</h2>";
        echo "<h1 style='font-size: 3em; color: #8B4513;'>" . $resultado['casa'] . "</h1>";
        echo "<p style='font-size: 1.2em;'>" . $casa->getDescricao() . "</p>";
        echo "<p><strong>Seus valores:</strong> " . implode(', ', $casa->getValores()) . "</p>";
        
        echo "<h3>Sua pontuação:</h3>";
        foreach ($resultado['pontuacao'] as $nomeCasa => $pontos) {
            $destaque = ($nomeCasa === $resultado['casa']) ? ' style="font-weight: bold;"' : '';
            echo "<p{$destaque}>{$nomeCasa}: {$pontos} pontos</p>";
        }
        echo "</div>";
    }
}
