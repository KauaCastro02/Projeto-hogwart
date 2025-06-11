<?php
namespace App\Entity;

class Tournament
{
    private int $id;
    private string $name;
    private string $description;
    private string $type; // 'quadribol', 'duelo', 'conhecimento', 'cooperativo'
    private array $rules;
    private \DateTime $startDate;
    private \DateTime $endDate;
    private string $location;
    private string $status; // 'planejado', 'ativo', 'finalizado'
    private array $participants = []; // IDs dos participantes
    private array $results = [];

    public function __construct(string $name, string $description, string $type, array $rules, \DateTime $startDate, \DateTime $endDate, string $location)
    {
        $this->name = $name;
        $this->description = $description;
        $this->type = $type;
        $this->rules = $rules;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->location = $location;
        $this->status = 'planejado';
    }

    public function getId(): int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }
    
    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getType(): string { return $this->type; }
    public function getRules(): array { return $this->rules; }
    public function getStartDate(): \DateTime { return $this->startDate; }
    public function getEndDate(): \DateTime { return $this->endDate; }
    public function getLocation(): string { return $this->location; }
    
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }
    
    public function getParticipants(): array { return $this->participants; }
    public function addParticipant(int $participantId): void 
    {
        if (!in_array($participantId, $this->participants)) {
            $this->participants[] = $participantId;
        }
    }
    
    public function getResults(): array { return $this->results; }
    public function addResult(int $participantId, int $score): void 
    {
        $this->results[$participantId] = $score;
    }
}

namespace App\Entity;

class Challenge
{
    private int $id;
    private int $tournamentId;
    private string $name;
    private string $description;
    private string $type; // 'individual', 'equipe', 'casa'
    private int $maxPoints;
    private \DateTime $date;
    private array $participants = [];
    private array $scores = [];

    public function __construct(int $tournamentId, string $name, string $description, string $type, int $maxPoints, \DateTime $date)
    {
        $this->tournamentId = $tournamentId;
        $this->name = $name;
        $this->description = $description;
        $this->type = $type;
        $this->maxPoints = $maxPoints;
        $this->date = $date;
    }

    public function getId(): int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }
    
    public function getTournamentId(): int { return $this->tournamentId; }
    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getType(): string { return $this->type; }
    public function getMaxPoints(): int { return $this->maxPoints; }
    public function getDate(): \DateTime { return $this->date; }
    
    public function getParticipants(): array { return $this->participants; }
    public function addParticipant(int $participantId): void 
    {
        $this->participants[] = $participantId;
    }
    
    public function getScores(): array { return $this->scores; }
    public function setScore(int $participantId, int $score): void 
    {
        $this->scores[$participantId] = $score;
    }
}

namespace App\Repository;

use App\Entity\Tournament;

class TournamentRepository
{
    private array $tournaments = [];
    private int $nextId = 1;

    public function save(Tournament $tournament): void
    {
        if (!isset($tournament->getId())) {
            $tournament->setId($this->nextId++);
        }
        $this->tournaments[$tournament->getId()] = $tournament;
    }

    public function findById(int $id): ?Tournament
    {
        return $this->tournaments[$id] ?? null;
    }

    public function findAll(): array
    {
        return array_values($this->tournaments);
    }

    public function findByStatus(string $status): array
    {
        return array_filter($this->tournaments, function(Tournament $tournament) use ($status) {
            return $tournament->getStatus() === $status;
        });
    }

    public function findByType(string $type): array
    {
        return array_filter($this->tournaments, function(Tournament $tournament) use ($type) {
            return $tournament->getType() === $type;
        });
    }
}

namespace App\Repository;

use App\Entity\Challenge;

class ChallengeRepository
{
    private array $challenges = [];
    private int $nextId = 1;

    public function save(Challenge $challenge): void
    {
        if (!isset($challenge->getId())) {
            $challenge->setId($this->nextId++);
        }
        $this->challenges[$challenge->getId()] = $challenge;
    }

    public function findById(int $id): ?Challenge
    {
        return $this->challenges[$id] ?? null;
    }

    public function findByTournament(int $tournamentId): array
    {
        return array_filter($this->challenges, function(Challenge $challenge) use ($tournamentId) {
            return $challenge->getTournamentId() === $tournamentId;
        });
    }
}

namespace App\Service;

use App\Entity\Tournament;
use App\Entity\Challenge;
use App\Repository\TournamentRepository;
use App\Repository\ChallengeRepository;

class TournamentService
{
    private TournamentRepository $tournamentRepo;
    private ChallengeRepository $challengeRepo;

    public function __construct(TournamentRepository $tournamentRepo, ChallengeRepository $challengeRepo)
    {
        $this->tournamentRepo = $tournamentRepo;
        $this->challengeRepo = $challengeRepo;
    }
    
    public function createTournament(string $name, string $description, string $type, array $rules, \DateTime $startDate, \DateTime $endDate, string $location): int
    {
        $tournament = new Tournament($name, $description, $type, $rules, $startDate, $endDate, $location);
        $this->tournamentRepo->save($tournament);
        return $tournament->getId();
    }

    public function registerParticipant(int $tournamentId, int $participantId): bool
    {
        $tournament = $this->tournamentRepo->findById($tournamentId);
        if (!$tournament || $tournament->getStatus() !== 'planejado') {
            return false;
        }

        $tournament->addParticipant($participantId);
        $this->tournamentRepo->save($tournament);
        return true;
    }

    public function startTournament(int $tournamentId): bool
    {
        $tournament = $this->tournamentRepo->findById($tournamentId);
        if (!$tournament || $tournament->getStatus() !== 'planejado') {
            return false;
        }

        $tournament->setStatus('ativo');
        $this->tournamentRepo->save($tournament);
        return true;
    }
    
    public function createChallenge(int $tournamentId, string $name, string $description, string $type, int $maxPoints, \DateTime $date): int
    {
        $challenge = new Challenge($tournamentId, $name, $description, $type, $maxPoints, $date);
        $this->challengeRepo->save($challenge);
        return $challenge->getId();
    }
    
    public function recordResult(int $challengeId, int $participantId, int $score): bool
    {
        $challenge = $this->challengeRepo->findById($challengeId);
        if (!$challenge) {
            return false;
        }

        $challenge->setScore($participantId, $score);
        $this->challengeRepo->save($challenge);

        $tournament = $this->tournamentRepo->findById($challenge->getTournamentId());
        if ($tournament) {
            $currentScore = $tournament->getResults()[$participantId] ?? 0;
            $tournament->addResult($participantId, $currentScore + $score);
            $this->tournamentRepo->save($tournament);
        }

        return true;
    }

    public function getLeaderboard(int $tournamentId): array
    {
        $tournament = $this->tournamentRepo->findById($tournamentId);
        if (!$tournament) {
            return [];
        }

        $results = $tournament->getResults();
        arsort($results); // Ordena por pontuação (maior primeiro)
        
        return $results;
    }

    public function finishTournament(int $tournamentId): bool
    {
        $tournament = $this->tournamentRepo->findById($tournamentId);
        if (!$tournament || $tournament->getStatus() !== 'ativo') {
            return false;
        }

        $tournament->setStatus('finalizado');
        $this->tournamentRepo->save($tournament);
        return true;
    }

    public function getTournamentsByStatus(string $status): array
    {
        return $this->tournamentRepo->findByStatus($status);
    }
    
    public function getChallengesByTournament(int $tournamentId): array
    {
        return $this->challengeRepo->findByTournament($tournamentId);
    }
}


namespace App\Controller;

use App\Service\TournamentService;

class TournamentController
{
    private TournamentService $service;

    public function __construct(TournamentService $service)
    {
        $this->service = $service;
    }

    public function createTournament(array $data): array
    {
        try {
            $startDate = new \DateTime($data['start_date']);
            $endDate = new \DateTime($data['end_date']);
            
            $tournamentId = $this->service->createTournament(
                $data['name'],
                $data['description'],
                $data['type'],
                $data['rules'],
                $startDate,
                $endDate,
                $data['location']
            );

            return [
                'success' => true,
                'message' => 'Torneio criado com sucesso',
                'tournament_id' => $tournamentId
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao criar torneio: ' . $e->getMessage()
            ];
        }
    }

    public function registerParticipant(int $tournamentId, int $participantId): array
    {
        $success = $this->service->registerParticipant($tournamentId, $participantId);
        
        return [
            'success' => $success,
            'message' => $success ? 'Participante inscrito' : 'Erro na inscrição'
        ];
    }

    public function startTournament(int $tournamentId): array
    {
        $success = $this->service->startTournament($tournamentId);
        
        return [
            'success' => $success,
            'message' => $success ? 'Torneio iniciado' : 'Erro ao iniciar torneio'
        ];
    }

    public function createChallenge(array $data): array
    {
        try {
            $date = new \DateTime($data['date']);
            
            $challengeId = $this->service->createChallenge(
                $data['tournament_id'],
                $data['name'],
                $data['description'],
                $data['type'],
                $data['max_points'],
                $date
            );

            return [
                'success' => true,
                'message' => 'Desafio criado com sucesso',
                'challenge_id' => $challengeId
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao criar desafio: ' . $e->getMessage()
            ];
        }
    }

    public function recordResult(int $challengeId, int $participantId, int $score): array
    {
        $success = $this->service->recordResult($challengeId, $participantId, $score);
        
        return [
            'success' => $success,
            'message' => $success ? 'Resultado registrado' : 'Erro ao registrar resultado'
        ];
    }

    public function getLeaderboard(int $tournamentId): array
    {
        $leaderboard = $this->service->getLeaderboard($tournamentId);
        
        return [
            'success' => true,
            'data' => $leaderboard
        ];
    }
}

namespace App;

use App\Entity\Tournament;
use App\Repository\TournamentRepository;
use App\Repository\ChallengeRepository;
use App\Service\TournamentService;
use App\Controller\TournamentController;

class TournamentDemo
{
    public static function run(): void
    {
    
        $tournamentRepo = new TournamentRepository();
        $challengeRepo = new ChallengeRepository();
        $service = new TournamentService($tournamentRepo, $challengeRepo);
        $controller = new TournamentController($service);

        echo "=== GERENCIAMENTO DE TORNEIOS - HOGWARTS ===\n\n";

        $tournamentData = [
            'name' => 'Torneio Tribruxo',
            'description' => 'Competição entre as três escolas de magia',
            'type' => 'cooperativo',
            'rules' => ['Apenas maiores de 17 anos', 'Máximo 3 participantes por escola'],
            'start_date' => '2024-10-31',
            'end_date' => '2024-12-25',
            'location' => 'Castelo de Hogwarts'
        ];

        $result = $controller->createTournament($tournamentData);
        echo "✅ {$result['message']}\n";
        $tournamentId = $result['tournament_id'];

        $participants = [101, 102, 103]; // IDs dos alunos
        foreach ($participants as $participantId) {
            $result = $controller->registerParticipant($tournamentId, $participantId);
            echo "📝 Participante {$participantId}: {$result['message']}\n";
        }

        $result = $controller->startTournament($tournamentId);
        echo "🚀 {$result['message']}\n\n";

        $challenges = [
            [
                'tournament_id' => $tournamentId,
                'name' => 'Dragão Húngaro',
                'description' => 'Recuperar ovo dourado guardado por dragão',
                'type' => 'individual',
                'max_points' => 100,
                'date' => '2024-11-15'
            ],
            [
                'tournament_id' => $tournamentId,
                'name' => 'Lago Negro',
                'description' => 'Resgatar pessoa querida do fundo do lago',
                'type' => 'individual',
                'max_points' => 100,
                'date' => '2024-12-01'
            ]
        ];

        $challengeIds = [];
        foreach ($challenges as $challengeData) {
            $result = $controller->createChallenge($challengeData);
            echo "🎯 Desafio '{$challengeData['name']}': {$result['message']}\n";
            $challengeIds[] = $result['challenge_id'];
        }

        echo "\n";

        $results = [
            // Desafio 1
            [$challengeIds[0], 101, 85], // Participante 101 fez 85 pontos
            [$challengeIds[0], 102, 92],
            [$challengeIds[0], 103, 78],
            // Desafio 2
            [$challengeIds[1], 101, 88],
            [$challengeIds[1], 102, 79],
            [$challengeIds[1], 103, 95]
        ];

        foreach ($results as [$challengeId, $participantId, $score]) {
            $result = $controller->recordResult($challengeId, $participantId, $score);
            echo "📊 Participante {$participantId} - {$score} pontos: {$result['message']}\n";
        }

        echo "\n=== CLASSIFICAÇÃO FINAL ===\n";
        $leaderboard = $controller->getLeaderboard($tournamentId);
        if ($leaderboard['success']) {
            $position = 1;
            foreach ($leaderboard['data'] as $participantId => $totalScore) {
                echo "{$position}º lugar - Participante {$participantId}: {$totalScore} pontos\n";
                $position++;
            }
        }

        $result = $service->finishTournament($tournamentId);
        echo "\n🏆 Torneio " . ($result ? "finalizado com sucesso!" : "erro ao finalizar") . "\n";
    }
}


TournamentDemo::run();
