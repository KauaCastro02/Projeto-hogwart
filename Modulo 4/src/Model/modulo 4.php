<?php

class ControleAcademico
{
    private $disciplinas = [];
    private $notas = [];
    private $infracoes = [];

    public function criarDisciplina($id, $nome, $professor)
    {
        $disciplina = new Disciplina($id, $nome, $professor);
        $this->disciplinas[$id] = $disciplina;
        return $disciplina;
    }

    public function getDisciplina($id)
    {
        return $this->disciplinas[$id] ?? null;
    }

    public function listarDisciplinas()
    {
        return $this->disciplinas;
    }

    public function lancarNota($id, $alunoId, $disciplinaId, $nota)
    {
        $notaObj = new Nota($id, $alunoId, $disciplinaId, $nota);
        $this->notas[] = $notaObj;
        return $notaObj;
    }

    public function getNotasAluno($alunoId)
    {
        $notasAluno = [];
        foreach ($this->notas as $nota) {
            if ($nota->getAlunoId() == $alunoId) {
                $notasAluno[] = $nota;
            }
        }
        return $notasAluno;
    }

    public function calcularMedia($alunoId)
    {
        $notas = $this->getNotasAluno($alunoId);
        if (empty($notas)) return 0;

        $soma = 0;
        foreach ($notas as $nota) {
            $soma += $nota->getNota();
        }
        return $soma / count($notas);
    }

    public function registrarInfracao($id, $alunoId, $descricao, $gravidade, $pontosPerdidos)
    {
        $infracao = new Infracao($id, $alunoId, $descricao, $gravidade, $pontosPerdidos);
        $this->infracoes[] = $infracao;
        return $infracao;
    }

    public function getInfracoesAluno($alunoId)
    {
        $infracoesAluno = [];
        foreach ($this->infracoes as $infracao) {
            if ($infracao->getAlunoId() == $alunoId) {
                $infracoesAluno[] = $infracao;
            }
        }
        return $infracoesAluno;
    }

    public function calcularPontosPerdidos($alunoId)
    {
        $infracoes = $this->getInfracoesAluno($alunoId);
        $totalPontos = 0;
        foreach ($infracoes as $infracao) {
            $totalPontos += $infracao->getPontosPerdidos();
        }
        return $totalPontos;
    }

    public function gerarRelatorioAluno($alunoId)
    {
        return [
            'aluno_id' => $alunoId,
            'media_geral' => $this->calcularMedia($alunoId),
            'total_notas' => count($this->getNotasAluno($alunoId)),
            'total_infracoes' => count($this->getInfracoesAluno($alunoId)),
            'pontos_perdidos' => $this->calcularPontosPerdidos($alunoId)
        ];
    }
}

$controle = new ControleAcademico();

$controle->criarDisciplina(1, "Poções", "Prof. Snape");
$controle->criarDisciplina(2, "Transfiguração", "Prof. McGonagall");

$controle->lancarNota(1, 101, 1, 8.5); // Aluno 101, Poções, nota 8.5
$controle->lancarNota(2, 101, 2, 7.0); // Aluno 101, Transfiguração, nota 7.0

$controle->registrarInfracao(1, 101, "Chegou atrasado", "leve", 5);

$relatorio = $controle->gerarRelatorioAluno(101);
print_r($relatorio);

?>