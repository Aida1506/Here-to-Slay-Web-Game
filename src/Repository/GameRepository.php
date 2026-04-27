<?php

namespace App\Repository;

use App\Database\Database;

// aceasta clasa gestioneaza operatiunile cu jocurile in baza de date
// foloseste clasa Database pentru a interactiona cu baza de date si ofera metode pentru a gasi salva si sterge jocuri
class GameRepository
{
    private Database $db;

    // aceasta functie initializeaza repository-ul cu baza de date
    // primeste instanta Database ca parametru o atribuie proprietatii private apoi creeaza tabela daca nu exista
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->createTable();
    }

    // aceasta functie privata creeaza tabela games daca nu exista
    // executa un query SQL pentru a crea tabela cu coloanele id name current_turn_player_id action_points main_deck discard_pile monster_deck active_monsters last_message created_at
    private function createTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS games (
                id VARCHAR(255) PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                current_turn_player_id VARCHAR(255),
                action_points INT DEFAULT 3,
                main_deck TEXT,
                discard_pile TEXT,
                monster_deck TEXT,
                active_monsters TEXT,
                last_message TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";

        $this->db->executeStatement($sql);
    }

    // aceasta functie returneaza toate jocurile din baza de date
    // executa un query pentru a selecta toate jocurile ordonate dupa created_at descendent apoi aplica hydrateGame pentru fiecare rand
    public function findAll(): array
    {
        $sql = "SELECT * FROM games ORDER BY created_at DESC";
        $rows = $this->db->executeQuery($sql);

        return array_map([$this, 'hydrateGame'], $rows);
    }

    // aceasta functie returneaza un joc dupa id
    // executa un query pentru a selecta jocul cu id-ul specific daca exista aplica hydrateGame altfel returneaza null
    public function findById(string $id): ?array
    {
        $sql = "SELECT * FROM games WHERE id = ?";
        $rows = $this->db->executeQuery($sql, [$id]);

        if (empty($rows)) {
            return null;
        }

        return $this->hydrateGame($rows[0]);
    }

    // aceasta functie salveaza un joc in baza de date
    // transforma jocul in format pentru baza de date apoi daca jocul exista deja actualizeaza altfel insereaza
    public function save(array $game): void
    {
        $data = $this->dehydrateGame($game);

        if ($this->findById($game['id'])) {
            $this->update($data);
            return;
        }

        $this->insert($data);
    }

    // aceasta functie sterge un joc dupa id
    // executa un query DELETE pentru jocul cu id-ul specific si returneaza daca s-a sters ceva
    public function delete(string $id): bool
    {
        $sql = "DELETE FROM games WHERE id = ?";
        return $this->db->executeStatement($sql, [$id]) > 0;
    }

    // aceasta functie privata insereaza un joc nou in baza de date
    // executa un query INSERT cu toate campurile jocului
    private function insert(array $data): void
    {
        $sql = "
            INSERT INTO games (
                id,
                name,
                current_turn_player_id,
                action_points,
                main_deck,
                discard_pile,
                monster_deck,
                active_monsters,
                last_message
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $this->db->executeStatement($sql, [
            $data['id'],
            $data['name'],
            $data['current_turn_player_id'],
            $data['action_points'],
            $data['main_deck'],
            $data['discard_pile'],
            $data['monster_deck'],
            $data['active_monsters'],
            $data['last_message']
        ]);
    }

    // aceasta functie privata actualizeaza un joc existent in baza de date
    // executa un query UPDATE pentru a actualiza toate campurile jocului unde id-ul se potriveste
    private function update(array $data): void
    {
        $sql = "
            UPDATE games
            SET
                name = ?,
                current_turn_player_id = ?,
                action_points = ?,
                main_deck = ?,
                discard_pile = ?,
                monster_deck = ?,
                active_monsters = ?,
                last_message = ?
            WHERE id = ?
        ";

        $this->db->executeStatement($sql, [
            $data['name'],
            $data['current_turn_player_id'],
            $data['action_points'],
            $data['main_deck'],
            $data['discard_pile'],
            $data['monster_deck'],
            $data['active_monsters'],
            $data['last_message'],
            $data['id']
        ]);
    }

    // aceasta functie privata transforma un rand din baza de date intr-un array de joc
    // primeste randul din baza de date si returneaza un array cu toate campurile convertite la tipurile corespunzatoare si array-urile json decode
    private function hydrateGame(array $row): array
    {
        return [
            'id' => $row['id'],
            'name' => $row['name'],
            'players' => [],
            'currentTurnPlayer' => null,
            'currentTurnPlayerId' => $row['current_turn_player_id'],
            'actionPoints' => (int) $row['action_points'],
            'mainDeck' => json_decode($row['main_deck'] ?? '[]', true) ?: [],
            'discardPile' => json_decode($row['discard_pile'] ?? '[]', true) ?: [],
            'monsterDeck' => json_decode($row['monster_deck'] ?? '[]', true) ?: [],
            'activeMonsters' => json_decode($row['active_monsters'] ?? '[]', true) ?: [],
            'lastMessage' => $row['last_message'] ?? ''
        ];
    }

    // aceasta functie privata transforma un array de joc in format pentru baza de date
    // primeste array-ul joc si returneaza un array cu toate campurile convertite la stringuri si array-urile json encode
    private function dehydrateGame(array $game): array
    {
        return [
            'id' => $game['id'],
            'name' => $game['name'],
            'current_turn_player_id' => $game['currentTurnPlayerId'] ?? null,
            'action_points' => $game['actionPoints'] ?? 3,
            'main_deck' => json_encode($game['mainDeck'] ?? []),
            'discard_pile' => json_encode($game['discardPile'] ?? []),
            'monster_deck' => json_encode($game['monsterDeck'] ?? []),
            'active_monsters' => json_encode($game['activeMonsters'] ?? []),
            'last_message' => $game['lastMessage'] ?? ''
        ];
    }
}
