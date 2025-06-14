<?php
namespace Hogwarts\ConviteCadastro\Interface;

interface ConviteInterface
{
    public function enviarConvite(int $alunoId): bool;
    public function confirmarRecebimento(string $codigoConvite): bool;
    public function listarConvites(): array;
    public function obterTaxaResposta(): array;
}

interface EmailInterface
{
    public function enviarEmail(string $destinatario, string $assunto, string $corpo): bool;
    public function gerarRelatorioCoruja(string $destinatario, string $mensagem): string;
}

namespace Hogwarts\ConviteCadastro\Entity;

use DateTime;

class Aluno
{
    private ?int $id = null;
    private string $nome;
    private string $email;
    private DateTime $dataNascimento;
    private string $endereco;
    private string $status = 'pre_selecionado';
    private DateTime $dataCadastro;

    public function __construct(
        string $nome,
        string $email,
        DateTime $dataNascimento,
        string $endereco
    ) {
        $this->nome = $nome;
        $this->email = $email;
        $this->dataNascimento = $dataNascimento;
        $this->endereco = $endereco;
        $this->dataCadastro = new DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getNome(): string { return $this->nome; }
    public function getEmail(): string { return $this->email; }
    public function getDataNascimento(): DateTime { return $this->dataNascimento; }
    public function getEndereco(): string { return $this->endereco; }
    public function getStatus(): string { return $this->status; }
    public function getDataCadastro(): DateTime { return $this->dataCadastro; }

    
    public function setId(int $id): void { $this->id = $id; }
    public function setStatus(string $status): void { $this->status = $status; }

 
    public function temIdadeMinima(): bool
    {
        $idade = $this->dataNascimento->diff(new DateTime())->y;
        return $idade >= 11;
    }

    public function podeReceberConvite(): bool
    {
        return $this->temIdadeMinima() && $this->status === 'pre_selecionado';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'email' => $this->email,
            'data_nascimento' => $this->dataNascimento->format('Y-m-d'),
            'endereco' => $this->endereco,
            'status' => $this->status,
            'data_cadastro' => $this->dataCadastro->format('Y-m-d H:i:s')
        ];
    }
}

class Convite
{
    private ?int $id = null;
    private int $alunoId;
    private string $codigo;
    private DateTime $dataEnvio;
    private ?DateTime $dataConfirmacao = null;
    private string $status = 'enviado';
    private string $conteudoCarta;

    public function __construct(int $alunoId, string $conteudoCarta)
    {
        $this->alunoId = $alunoId;
        $this->conteudoCarta = $conteudoCarta;
        $this->codigo = $this->gerarCodigo();
        $this->dataEnvio = new DateTime();
    }

    private function gerarCodigo(): string
    {
        return 'HOG' . date('Y') . strtoupper(substr(md5(uniqid()), 0, 6));
    }

    public function getId(): ?int { return $this->id; }
    public function getAlunoId(): int { return $this->alunoId; }
    public function getCodigo(): string { return $this->codigo; }
    public function getDataEnvio(): DateTime { return $this->dataEnvio; }
    public function getDataConfirmacao(): ?DateTime { return $this->dataConfirmacao; }
    public function getStatus(): string { return $this->status; }
    public function getConteudoCarta(): string { return $this->conteudoCarta; }


    public function setId(int $id): void { $this->id = $id; }


    public function confirmar(): bool
    {
        if ($this->status === 'enviado') {
            $this->status = 'confirmado';
            $this->dataConfirmacao = new DateTime();
            return true;
        }
        return false;
    }

    public function estaExpirado(): bool
    {
        $diasLimite = 30;
        $agora = new DateTime();
        $diff = $this->dataEnvio->diff($agora)->days;
        return $diff > $diasLimite && $this->status === 'enviado';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'aluno_id' => $this->alunoId,
            'codigo' => $this->codigo,
            'data_envio' => $this->dataEnvio->format('Y-m-d H:i:s'),
            'data_confirmacao' => $this->dataConfirmacao?->format('Y-m-d H:i:s'),
            'status' => $this->status
        ];
    }
}

namespace Hogwarts\ConviteCadastro\Service;

use Hogwarts\ConviteCadastro\Entity\Aluno;
use Hogwarts\ConviteCadastro\Entity\Convite;
use Hogwarts\ConviteCadastro\Interface\ConviteInterface;
use Hogwarts\ConviteCadastro\Interface\EmailInterface;
use Hogwarts\ConviteCadastro\Repository\AlunoRepository;
use Hogwarts\ConviteCadastro\Repository\ConviteRepository;

class ConviteService implements ConviteInterface
{
    private AlunoRepository $alunoRepository;
    private ConviteRepository $conviteRepository;
    private EmailInterface $emailService;

    public function __construct(
        AlunoRepository $alunoRepository,
        ConviteRepository $conviteRepository,
        EmailInterface $emailService
    ) {
        $this->alunoRepository = $alunoRepository;
        $this->conviteRepository = $conviteRepository;
        $this->emailService = $emailService;
    }

    public function enviarConvite(int $alunoId): bool
    {
        $aluno = $this->alunoRepository->buscarPorId($alunoId);
        
        if (!$aluno || !$aluno->podeReceberConvite()) {
            return false;
        }

        if ($this->conviteRepository->existeConvitePendente($alunoId)) {
            return false;
        }

        $conteudoCarta = $this->gerarConteudoCarta($aluno);
        $convite = new Convite($alunoId, $conteudoCarta);
        
        $this->conviteRepository->salvar($convite);

        $emailEnviado = $this->emailService->enviarEmail(
            $aluno->getEmail(),
            'Convite para Hogwarts - Escola de Magia e Bruxaria',
            $conteudoCarta
        );

        if ($emailEnviado) {
    
            $relatorioCoruja = $this->emailService->gerarRelatorioCoruja(
                $aluno->getEndereco(),
                "Carta-convite para {$aluno->getNome()} - Código: {$convite->getCodigo()}"
            );

            $aluno->setStatus('convidado');
            $this->alunoRepository->atualizar($aluno);
            
            return true;
        }

        return false;
    }

    public function confirmarRecebimento(string $codigoConvite): bool
    {
        $convite = $this->conviteRepository->buscarPorCodigo($codigoConvite);
        
        if (!$convite || $convite->estaExpirado()) {
            return false;
        }

        if ($convite->confirmar()) {
            $this->conviteRepository->atualizar($convite);
            
            $aluno = $this->alunoRepository->buscarPorId($convite->getAlunoId());
            if ($aluno) {
                $aluno->setStatus('confirmado');
                $this->alunoRepository->atualizar($aluno);
            }
            
            return true;
        }

        return false;
    }

    public function listarConvites(): array
    {
        return $this->conviteRepository->listarTodos();
    }

    public function obterTaxaResposta(): array
    {
        $convites = $this->conviteRepository->listarTodos();
        $totalEnviados = count($convites);
        $confirmados = count(array_filter($convites, fn($c) => $c->getStatus() === 'confirmado'));
        
        return [
            'total_enviados' => $totalEnviados,
            'total_confirmados' => $confirmados,
            'taxa_resposta' => $totalEnviados > 0 ? round(($confirmados / $totalEnviados) * 100, 2) : 0
        ];
    }

    private function gerarConteudoCarta(Aluno $aluno): string
    {
        return "
ESCOLA DE MAGIA E BRUXARIA DE HOGWARTS

Caro(a) {$aluno->getNome()},

Temos o prazer de informá-lo(a) de que foi aceito(a) na Escola de Magia e Bruxaria de Hogwarts.

🚂 EXPRESSO DE HOGWARTS
Data de embarque: 1º de Setembro
Horário: 11h00
Local: Plataforma 9¾ - Estação King's Cross, Londres

📚 MATERIAIS NECESSÁRIOS:
• Varinha mágica
• Caldeirão (tamanho padrão 2, estanho)
• Conjunto de balança de latão
• Kit de ingredientes para poções
• Telescópio
• Conjunto de frascos de vidro
• Livros didáticos do primeiro ano
• Uniforme escolar completo
• Animal de estimação: coruja, gato ou sapo

Para confirmar sua participação, acesse o sistema com o código de confirmação.

Atenciosamente,

Profª. Minerva McGonagall
Vice-Diretora
        ";
    }
}

class EmailService implements EmailInterface
{
    private array $logEmails = [];

    public function enviarEmail(string $destinatario, string $assunto, string $corpo): bool
    {
        // Simulação de envio de email (em produção usar PHPMailer, SwiftMailer, etc.)
        $this->logEmails[] = [
            'destinatario' => $destinatario,
            'assunto' => $assunto,
            'corpo' => $corpo,
            'data_envio' => new \DateTime(),
            'status' => 'enviado'
        ];

        // Simular sucesso do envio
        return true;
    }

    public function gerarRelatorioCoruja(string $destinatario, string $mensagem): string
    {
        return "
═══════════════════════════════════════
🦉 RELATÓRIO PARA ENVIO POR CORUJA 🦉
═══════════════════════════════════════

Destinatário: {$destinatario}
Data: " . date('d/m/Y H:i:s') . "

Mensagem: {$mensagem}

Instruções: Anexar carta física e enviar
pela coruja mais próxima disponível.
═══════════════════════════════════════
        ";
    }

    public function obterLogEmails(): array
    {
        return $this->logEmails;
    }
}

namespace Hogwarts\ConviteCadastro\Repository;

use Hogwarts\ConviteCadastro\Entity\Aluno;

class AlunoRepository
{
    private array $alunos = [];
    private int $proximoId = 1;

    public function salvar(Aluno $aluno): void
    {
        if ($aluno->getId() === null) {
            $aluno->setId($this->proximoId++);
        }
        $this->alunos[$aluno->getId()] = $aluno;
    }

    public function buscarPorId(int $id): ?Aluno
    {
        return $this->alunos[$id] ?? null;
    }

    public function buscarPorEmail(string $email): ?Aluno
    {
        foreach ($this->alunos as $aluno) {
            if ($aluno->getEmail() === $email) {
                return $aluno;
            }
        }
        return null;
    }

    public function listarPreSelecionados(): array
    {
        return array_filter($this->alunos, fn($a) => $a->getStatus() === 'pre_selecionado');
    }

    public function atualizar(Aluno $aluno): void
    {
        if ($aluno->getId() && isset($this->alunos[$aluno->getId()])) {
            $this->alunos[$aluno->getId()] = $aluno;
        }
    }

    public function listarTodos(): array
    {
        return array_values($this->alunos);
    }
}

class ConviteRepository
{
    private array $convites = [];
    private int $proximoId = 1;

    public function salvar(Convite $convite): void
    {
        if ($convite->getId() === null) {
            $convite->setId($this->proximoId++);
        }
        $this->convites[$convite->getId()] = $convite;
    }

    public function buscarPorId(int $id): ?Convite
    {
        return $this->convites[$id] ?? null;
    }

    public function buscarPorCodigo(string $codigo): ?Convite
    {
        foreach ($this->convites as $convite) {
            if ($convite->getCodigo() === $codigo) {
                return $convite;
            }
        }
        return null;
    }

    public function existeConvitePendente(int $alunoId): bool
    {
        foreach ($this->convites as $convite) {
            if ($convite->getAlunoId() === $alunoId && $convite->getStatus() === 'enviado') {
                return true;
            }
        }
        return false;
    }

    public function atualizar(Convite $convite): void
    {
        if ($convite->getId() && isset($this->convites[$convite->getId()])) {
            $this->convites[$convite->getId()] = $convite;
        }
    }

    public function listarTodos(): array
    {
        return array_values($this->convites);
    }

    public function listarPorAluno(int $alunoId): array
    {
        return array_filter($this->convites, fn($c) => $c->getAlunoId() === $alunoId);
    }
}

namespace Hogwarts\ConviteCadastro\Controller;

use Hogwarts\ConviteCadastro\Entity\Aluno;
use Hogwarts\ConviteCadastro\Service\ConviteService;
use Hogwarts\ConviteCadastro\Repository\AlunoRepository;
use DateTime;

class AdminController
{
    private ConviteService $conviteService;
    private AlunoRepository $alunoRepository;

    public function __construct(ConviteService $conviteService, AlunoRepository $alunoRepository)
    {
        $this->conviteService = $conviteService;
        $this->alunoRepository = $alunoRepository;
    }

    public function cadastrarAluno(array $dados): array
    {
        try {
            $aluno = new Aluno(
                $dados['nome'],
                $dados['email'],
                new DateTime($dados['data_nascimento']),
                $dados['endereco']
            );

            if ($this->alunoRepository->buscarPorEmail($aluno->getEmail())) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'E-mail já cadastrado no sistema'
                ];
            }

            $this->alunoRepository->salvar($aluno);

            return [
                'sucesso' => true,
                'mensagem' => 'Aluno cadastrado com sucesso',
                'aluno' => $aluno->toArray()
            ];
        } catch (\Exception $e) {
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao cadastrar aluno: ' . $e->getMessage()
            ];
        }
    }

    /**
     * História 1: Enviar automaticamente carta-convite
     */
    public function enviarConvite(int $alunoId): array
    {
        $enviado = $this->conviteService->enviarConvite($alunoId);

        return [
            'sucesso' => $enviado,
            'mensagem' => $enviado 
                ? 'Convite enviado com sucesso' 
                : 'Erro ao enviar convite (aluno não encontrado ou já convidado)'
        ];
    }

    public function confirmarRecebimento(string $codigo): array
    {
        $confirmado = $this->conviteService->confirmarRecebimento($codigo);

        return [
            'sucesso' => $confirmado,
            'mensagem' => $confirmado 
                ? 'Recebimento confirmado! Bem-vindo(a) a Hogwarts!' 
                : 'Código inválido ou convite expirado'
        ];
    }

    public function listarConvites(): array
    {
        $convites = $this->conviteService->listarConvites();
        $estatisticas = $this->conviteService->obterTaxaResposta();

        return [
            'convites' => array_map(fn($c) => $c->toArray(), $convites),
            'estatisticas' => $estatisticas
        ];
    }

    public function listarAlunosPreSelecionados(): array
    {
        $alunos = $this->alunoRepository->listarPreSelecionados();
        return array_map(fn($a) => $a->toArray(), $alunos);
    }
}

$alunoRepository = new \Hogwarts\ConviteCadastro\Repository\AlunoRepository();
$conviteRepository = new \Hogwarts\ConviteCadastro\Repository\ConviteRepository();
$emailService = new \Hogwarts\ConviteCadastro\Service\EmailService();

$conviteService = new \Hogwarts\ConviteCadastro\Service\ConviteService(
    $alunoRepository,
    $conviteRepository,
    $emailService
);

$adminController = new \Hogwarts\ConviteCadastro\Controller\AdminController(
    $conviteService,
    $alunoRepository
);

echo "🏰 SISTEMA DE GESTÃO HOGWARTS - MÓDULO 1 🏰\n";
echo "==========================================\n\n";

echo "📝 CADASTRANDO ALUNO PRÉ-SELECIONADO\n";
$resultado = $adminController->cadastrarAluno([
    'nome' => 'Harry Potter',
    'email' => 'harry.potter@privetdrive.com',
    'data_nascimento' => '1980-07-31',
    'endereco' => '4 Privet Drive, Little Whinging, Surrey'
]);

echo "Status: " . ($resultado['sucesso'] ? '✅ Sucesso' : '❌ Erro') . "\n";
echo "Mensagem: {$resultado['mensagem']}\n\n";

if ($resultado['sucesso']) {
    $alunoId = $resultado['aluno']['id'];
    
    echo "📧 ENVIANDO CARTA-CONVITE\n";
    $conviteResult = $adminController->enviarConvite($alunoId);
    echo "Status: " . ($conviteResult['sucesso'] ? '✅ Sucesso' : '❌ Erro') . "\n";
    echo "Mensagem: {$conviteResult['mensagem']}\n\n";
    
    echo "✅ CONFIRMANDO RECEBIMENTO\n";
    $convites = $conviteService->listarConvites();
    if (!empty($convites)) {
        $codigo = $convites[0]->getCodigo();
        $confirmResult = $adminController->confirmarRecebimento($codigo);
        echo "Código usado: {$codigo}\n";
        echo "Status: " . ($confirmResult['sucesso'] ? '✅ Confirmado' : '❌ Erro') . "\n";
        echo "Mensagem: {$confirmResult['mensagem']}\n\n";
    }
    
    echo "📊 RELATÓRIO DE CONVITES\n";
    $relatorio = $adminController->listarConvites();
    echo "Total enviados: {$relatorio['estatisticas']['total_enviados']}\n";
    echo "Total confirmados: {$relatorio['estatisticas']['total_confirmados']}\n";
    echo "Taxa de resposta: {$relatorio['estatisticas']['taxa_resposta']}%\n\n";
    
    echo "📋 LISTA DE CONVITES:\n";
    foreach ($relatorio['convites'] as $convite) {
        echo "- Código: {$convite['codigo']} | Status: {$convite['status']}\n";
    }
}
