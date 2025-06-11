<?php

namespace Hogwarts\Services;

use Hogwarts\Models\Casa;

class SelecaoService
{
    private $casas = [];
    private $perguntas = [];
    private $resultados = [];

    public function __construct()
    {
        $this->criarCasas();
        $this->criarPerguntas();
    }

    private function criarCasas()
    {
        $this->casas = [
            'Grifinória' => new Casa('Grifinória', 'Corajosos e audaciosos', ['Coragem', 'Audácia']),
            'Sonserina' => new Casa('Sonserina', 'Ambiciosos e astutos', ['Ambição', 'Astúcia']),
            'Corvinal' => new Casa('Corvinal', 'Sábios e criativos', ['Inteligência', 'Criatividade']),
            'Lufa-Lufa' => new Casa('Lufa-Lufa', 'Leais e trabalhadores', ['Lealdade', 'Trabalho'])
        ];
    }

    private function criarPerguntas()
    {
        $this->perguntas = [
            [
                'pergunta' => 'Qual sua maior qualidade?',
                'opcoes' => [
                    'A' => ['texto' => 'Coragem', 'casa' => 'Grifinória'],
                    'B' => ['texto' => 'Ambição', 'casa' => 'Sonserina'],
                    'C' => ['texto' => 'Inteligência', 'casa' => 'Corvinal'],
                    'D' => ['texto' => 'Lealdade', 'casa' => 'Lufa-Lufa']
                ]
            ],
            [
                'pergunta' => 'O que você prefere fazer?',
                'opcoes' => [
                    'A' => ['texto' => 'Aventuras', 'casa' => 'Grifinória'],
                    'B' => ['texto' => 'Competir', 'casa' => 'Sonserina'],
                    'C' => ['texto' => 'Estudar', 'casa' => 'Corvinal'],
                    'D' => ['texto' => 'Ajudar', 'casa' => 'Lufa-Lufa']
                ]
            ],
            [
                'pergunta' => 'Como você quer ser lembrado?',
                'opcoes' => [
                    'A' => ['texto' => 'Herói', 'casa' => 'Grifinória'],
                    'B' => ['texto' => 'Líder', 'casa' => 'Sonserina'],
                    'C' => ['texto' => 'Sábio', 'casa' => 'Corvinal'],
                    'D' => ['texto' => 'Amigo', 'casa' => 'Lufa-Lufa']
                ]
            ]
        ];
    }

    public function getPerguntas()
    {
        return $this->perguntas;
    }

    public function getCasas()
    {
        return $this->casas;
    }

    public function realizarSelecao($alunoId, $respostas)
    {
        $pontuacao = [
            'Grifinória' => 0,
            'Sonserina' => 0,
            'Corvinal' => 0,
            'Lufa-Lufa' => 0
        ];

        foreach ($respostas as $resposta) {
            $pergunta = $this->perguntas[$resposta['pergunta']];
            $opcao = $pergunta['opcoes'][$resposta['opcao']];
            $pontuacao[$opcao['casa']]++;
        }

        $casaEscolhida = array_keys($pontuacao, max($pontuacao))[0];

        $this->casas[$casaEscolhida]->adicionarAluno();

        $this->resultados[$alunoId] = [
            'casa' => $casaEscolhida,
            'pontuacao' => $pontuacao,
            'data' => date('Y-m-d H:i:s')
        ];

        return $this->resultados[$alunoId];
    }

    public function getResultado($alunoId)
    {
        return $this->resultados[$alunoId] ?? null;
    }
}
