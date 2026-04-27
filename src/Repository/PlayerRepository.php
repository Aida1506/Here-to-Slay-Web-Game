<?php

namespace App\Repository;

use App\Database\Database;

// aceasta clasa gestioneaza operatiunile cu jucatorii in baza de date
// foloseste clasa Database pentru a interactiona cu baza de date si ofera metode pentru a gasi salva si sterge jucatori
class PlayerRepository
{
    private Database $db;

    // aceasta functie initializeaza repository-ul cu baza de date
    // primeste instanta Database ca parametru o atribuie proprietatii private apoi creeaza tabela daca nu exista
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->createTable();
    }

    // aceasta functie privata creeaza tabela players daca nu exista
    // executa un query SQL pentru a crea tabela cu coloanele id name party_leader party_leader_class hand party slain_monsters game_id role created_at si foreign key catre games
    private function createTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS players (
                id VARCHAR(255) PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                party_leader VARCHAR(255),
                party_leader_class VARCHAR(255),
                hand TEXT,
                party TEXT,
                slain_monsters TEXT,
                game_id VARCHAR(255),
                role VARCHAR(50) DEFAULT 'player',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
            )
        ";

        $this->db->executeStatement($sql);
    }

    // aceasta functie returneaza toti jucatorii din baza de date
    // executa un query pentru a selecta toti jucatorii ordonati dupa created_at descendent apoi aplica hydratePlayer pentru fiecare rand
    public function findAll(): array
    {
        $sql = "SELECT * FROM players ORDER BY created_at DESC";
        $rows = $this->db->executeQuery($sql);

        return array_map([$this, 'hydratePlayer'], $rows);
    }

    // aceasta functie returneaza jucatorii dintr-un joc specific
    // executa un query pentru a selecta jucatorii cu game_id specific ordonati dupa id apoi aplica hydratePlayer pentru fiecare rand
    public function findByGameId(string $gameId): array
    {
        $sql = "SELECT * FROM players WHERE game_id = ? ORDER BY id ASC";
        $rows = $this->db->executeQuery($sql, [$gameId]);

        return array_map([$this, 'hydratePlayer'], $rows);
    }

    // aceasta functie returneaza un jucator dupa id
    // executa un query pentru a selecta jucatorul cu id-ul specific daca exista aplica hydratePlayer altfel returneaza null
    public function findById(string $id): ?array
    {
        $sql = "SELECT * FROM players WHERE id = ?";
        $rows = $this->db->executeQuery($sql, [$id]);

        if (empty($rows)) {
            return null;
        }

        return $this->hydratePlayer($rows[0]);
    }

    // aceasta functie salveaza un jucator in baza de date
    // transforma jucatorul in format pentru baza de date apoi daca jucatorul exista deja actualizeaza altfel insereaza
    public function save(array $player): void
    {
        $data = $this->dehydratePlayer($player);

        if ($this->findById($player['id'])) {
            $this->update($data);
            return;
        }

        $this->insert($data);
    }

    // aceasta functie salveaza multi jucatori in baza de date
    // parcurge array-ul de jucatori si apeleaza save pentru fiecare
    public function saveMultiple(array $players): void
    {
        foreach ($players as $player) {
            $this->save($player);
        }
    }

    // aceasta functie sterge toti jucatorii dintr-un joc dupa game_id
    // executa un query DELETE pentru jucatorii cu game_id specific si returneaza numarul de randuri sterse
    public function deleteByGameId(string $gameId): int
    {
        $sql = "DELETE FROM players WHERE game_id = ?";
        return $this->db->executeStatement($sql, [$gameId]);
    }

    // aceasta functie privata insereaza un jucator nou in baza de date
    // executa un query INSERT cu toate campurile jucatorului
    private function insert(array $data): void
    {
        $sql = "
            INSERT INTO players (
                id,
                name,
                party_leader,
                party_leader_class,
                hand,
                party,
                slain_monsters,
                game_id,
                role
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $this->db->executeStatement($sql, [
            $data['id'],
            $data['name'],
            $data['party_leader'],
            $data['party_leader_class'],
            $data['hand'],
            $data['party'],
            $data['slain_monsters'],
            $data['game_id'],
            $data['role']
        ]);
    }

    // aceasta functie privata actualizeaza un jucator existent in baza de date
    // executa un query UPDATE pentru a actualiza toate campurile jucatorului unde id-ul se potriveste
    private function update(array $data): void
    {
        $sql = "
            UPDATE players
            SET
                name = ?,
                party_leader = ?,
                party_leader_class = ?,
                hand = ?,
                party = ?,
                slain_monsters = ?,
                game_id = ?,
                role = ?
            WHERE id = ?
        ";

        $this->db->executeStatement($sql, [
            $data['name'],
            $data['party_leader'],
            $data['party_leader_class'],
            $data['hand'],
            $data['party'],
            $data['slain_monsters'],
            $data['game_id'],
            $data['role'],
            $data['id']
        ]);
    }

    // aceasta functie privata transforma un rand din baza de date intr-un array de jucator
    // primeste randul din baza de date si returneaza un array cu toate campurile convertite la tipurile corespunzatoare si array-urile json decode
    private function hydratePlayer(array $row): array
    {
        return [
            'id' => $row['id'],
            'name' => $row['name'],
            'partyLeader' => $row['party_leader'],
            'partyLeaderClass' => $row['party_leader_class'],
            'hand' => json_decode($row['hand'] ?? '[]', true) ?: [],
            'party' => json_decode($row['party'] ?? '[]', true) ?: [],
            'slainMonsters' => json_decode($row['slain_monsters'] ?? '[]', true) ?: [],
            'gameId' => $row['game_id'],
            'role' => $row['role']
        ];
    }

    // aceasta functie privata transforma un array de jucator in format pentru baza de date
    // primeste array-ul jucator si returneaza un array cu toate campurile convertite la stringuri si array-urile json encode
    private function dehydratePlayer(array $player): array
    {
        return [
            'id' => $player['id'],
            'name' => $player['name'],
            'party_leader' => $player['partyLeader'] ?? '',
            'party_leader_class' => $player['partyLeaderClass'] ?? '',
            'hand' => json_encode($player['hand'] ?? []),
            'party' => json_encode($player['party'] ?? []),
            'slain_monsters' => json_encode($player['slainMonsters'] ?? []),
            'game_id' => $player['gameId'] ?? null,
            'role' => $player['role'] ?? 'player'
        ];
    }
}
