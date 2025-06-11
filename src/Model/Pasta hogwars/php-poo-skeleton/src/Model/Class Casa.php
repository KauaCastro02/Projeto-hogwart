<?php

namespace Hogwarts\Models;

class Casa
{
    private $nome;
    private $descricao;
    private $valores;
    private $totalAlunos;

    public function __construct($nome, $descricao, $valores)
    {
        $this->nome = $nome;
        $this->descricao = $descricao;
        $this->valores = $valores;
        $this->totalAlunos = 0;
    }

    public function getNome() { return $this->nome; }
    public function getDescricao() { return $this->descricao; }
    public function getValores() { return $this->valores; }
    public function getTotalAlunos() { return $this->totalAlunos; }

    public function adicionarAluno()
    {
        $this->totalAlunos++;
    }
}