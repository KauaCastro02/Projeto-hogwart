<?php

namespace App\Modules\AcademicControl;

class Disciplina
{
    private $id;
    private $nome;
    private $professor;
    private $alunos = [];

    public function __construct($id, $nome, $professor)
    {
        $this->id = $id;
        $this->nome = $nome;
        $this->professor = $professor;
    }

    public function getId() { return $this->id; }
    public function getNome() { return $this->nome; }
    public function getProfessor() { return $this->professor; }

    public function adicionarAluno($alunoId)
    {
        $this->alunos[] = $alunoId;
    }

    public function getAlunos()
    {
        return $this->alunos;
    }
}
