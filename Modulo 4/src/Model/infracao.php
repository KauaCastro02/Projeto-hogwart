<?php
class Infracao
{
    private $id;
    private $alunoId;
    private $descricao;
    private $gravidade; // leve, media, grave
    private $pontosPerdidos;
    private $data;

    public function __construct($id, $alunoId, $descricao, $gravidade, $pontosPerdidos)
    {
        $this->id = $id;
        $this->alunoId = $alunoId;
        $this->descricao = $descricao;
        $this->gravidade = $gravidade;
        $this->pontosPerdidos = $pontosPerdidos;
        $this->data = date('Y-m-d');
    }

    public function getId() { return $this->id; }
    public function getAlunoId() { return $this->alunoId; }
    public function getDescricao() { return $this->descricao; }
    public function getGravidade() { return $this->gravidade; }
    public function getPontosPerdidos() { return $this->pontosPerdidos; }
    public function getData() { return $this->data; }
}

/**
 * Classe Controle Acadêmico
 */