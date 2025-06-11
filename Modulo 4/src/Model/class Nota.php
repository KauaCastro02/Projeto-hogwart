<?php
class Nota
{
    private $id;
    private $alunoId;
    private $disciplinaId;
    private $nota;
    private $data;

    public function __construct($id, $alunoId, $disciplinaId, $nota)
    {
        $this->id = $id;
        $this->alunoId = $alunoId;
        $this->disciplinaId = $disciplinaId;
        $this->nota = $nota;
        $this->data = date('Y-m-d');
    }

    public function getId() { return $this->id; }
    public function getAlunoId() { return $this->alunoId; }
    public function getDisciplinaId() { return $this->disciplinaId; }
    public function getNota() { return $this->nota; }
    public function getData() { return $this->data; }

    public function isAprovado()
    {
        return $this->nota >= 6.0;
    }
}