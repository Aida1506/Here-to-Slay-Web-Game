<?php

namespace App\Repository;

use App\Database\Database;

// aceasta clasa gestioneaza operatiunile cu cartile in baza de date
// foloseste clasa Database pentru a interactiona cu baza de date si ofera metode pentru a gasi carti
class CardRepository
{
    private Database $db;

    // aceasta functie initializeaza repository-ul cu baza de date
    // primeste instanta Database ca parametru o atribuie proprietatii private apoi creeaza tabela si adauga datele initiale daca e necesar
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->createTable();
        $this->seedCards();
    }

    // aceasta functie privata creeaza tabela cards daca nu exista
    // executa un query SQL pentru a crea tabela cu coloanele id name type class description roll_requirement
    private function createTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS cards (
                id VARCHAR(255) PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                type VARCHAR(50) NOT NULL,
                class VARCHAR(50),
                description TEXT,
                roll_requirement INT
            )
        ";

        $this->db->executeStatement($sql);
    }

    // aceasta functie privata adauga cartile default in tabela daca e goala
    // verifica daca exista carti in tabela daca nu parcurge cartile default si le insereaza in baza de date
    private function seedCards(): void
    {
        $count = (int) $this->db->executeQuery("SELECT COUNT(*) as count FROM cards")[0]['count'];

        if ($count > 0) {
            return;
        }

        foreach ($this->getDefaultCards() as $card) {
            $this->db->executeStatement(
                "INSERT INTO cards (id, name, type, class, description, roll_requirement) VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $card['id'],
                    $card['name'],
                    $card['type'],
                    $card['class'],
                    $card['description'],
                    $card['rollRequirement']
                ]
            );
        }
    }

    // aceasta functie returneaza toate cartile din baza de date
    // executa un query pentru a selecta toate cartile ordonate dupa id apoi aplica hydrateCard pentru fiecare rand
    public function findAll(): array
    {
        $rows = $this->db->executeQuery("SELECT * FROM cards ORDER BY id ASC");
        return array_map([$this, 'hydrateCard'], $rows);
    }

    // aceasta functie returneaza cartile de un anumit tip
    // executa un query pentru a selecta cartile cu tipul specific ordonate dupa id apoi aplica hydrateCard pentru fiecare rand
    public function findByType(string $type): array
    {
        $rows = $this->db->executeQuery("SELECT * FROM cards WHERE type = ? ORDER BY id ASC", [$type]);
        return array_map([$this, 'hydrateCard'], $rows);
    }

    // aceasta functie privata transforma un rand din baza de date intr-un array de carte
    // primeste randul din baza de date si returneaza un array cu id name type class description rollRequirement convertit la int daca exista
    private function hydrateCard(array $row): array
    {
        return [
            'id' => $row['id'],
            'name' => $row['name'],
            'type' => $row['type'],
            'class' => $row['class'] ?? '',
            'description' => $row['description'] ?? '',
            'rollRequirement' => isset($row['roll_requirement']) ? (int) $row['roll_requirement'] : null
        ];
    }

    // aceasta functie privata returneaza cartile default pentru initializare
    // returneaza un array cu 18 carti fiecare continand id name type class description rollRequirement
    private function getDefaultCards(): array
    {
        return [
            ['id' => 'c1', 'name' => 'Brave Fighter', 'type' => 'hero', 'class' => 'fighter', 'description' => 'Roll 5+ to draw a card.', 'rollRequirement' => 5],
            ['id' => 'c2', 'name' => 'Shield Guardian', 'type' => 'hero', 'class' => 'guardian', 'description' => 'Roll 6+ to protect a hero.', 'rollRequirement' => 6],
            ['id' => 'c3', 'name' => 'Forest Ranger', 'type' => 'hero', 'class' => 'ranger', 'description' => 'Roll 7+ to search the deck.', 'rollRequirement' => 7],
            ['id' => 'c4', 'name' => 'Sneaky Thief', 'type' => 'hero', 'class' => 'thief', 'description' => 'Roll 8+ to steal a card.', 'rollRequirement' => 8],
            ['id' => 'c5', 'name' => 'Spark Wizard', 'type' => 'hero', 'class' => 'wizard', 'description' => 'Roll 6+ to use magic power.', 'rollRequirement' => 6],
            ['id' => 'c6', 'name' => 'Lucky Bard', 'type' => 'hero', 'class' => 'bard', 'description' => 'Roll 5+ to add a bonus.', 'rollRequirement' => 5],
            ['id' => 'c7', 'name' => 'Iron Sword', 'type' => 'item', 'class' => 'item', 'description' => '+1 bonus when attacking monsters.', 'rollRequirement' => null],
            ['id' => 'c8', 'name' => 'Cursed Helmet', 'type' => 'item', 'class' => 'item', 'description' => '+1 bonus when attacking monsters.', 'rollRequirement' => null],
            ['id' => 'c9', 'name' => 'Fire Spell', 'type' => 'magic', 'class' => 'magic', 'description' => 'Replace one active monster.', 'rollRequirement' => null],
            ['id' => 'c10', 'name' => 'Healing Spell', 'type' => 'magic', 'class' => 'magic', 'description' => 'Draw one card from the deck.', 'rollRequirement' => null],
            ['id' => 'c11', 'name' => 'Plus Two', 'type' => 'modifier', 'class' => 'modifier', 'description' => '+2 bonus when attacking monsters.', 'rollRequirement' => null],
            ['id' => 'c12', 'name' => 'Minus Two', 'type' => 'modifier', 'class' => 'modifier', 'description' => '+1 bonus when attacking monsters.', 'rollRequirement' => null],
            ['id' => 'c13', 'name' => 'Challenge', 'type' => 'challenge', 'class' => 'challenge', 'description' => 'The next player discards one card.', 'rollRequirement' => null],
            ['id' => 'c14', 'name' => 'Heroic Guardian', 'type' => 'hero', 'class' => 'guardian', 'description' => 'Roll 7+ to draw two cards.', 'rollRequirement' => 7],
            ['id' => 'c15', 'name' => 'Wild Bard', 'type' => 'hero', 'class' => 'bard', 'description' => 'Roll 8+ to play another card.', 'rollRequirement' => 8],
            ['id' => 'c16', 'name' => 'Arcane Wizard', 'type' => 'hero', 'class' => 'wizard', 'description' => 'Roll 7+ to discard an enemy item.', 'rollRequirement' => 7],
            ['id' => 'c17', 'name' => 'Fast Thief', 'type' => 'hero', 'class' => 'thief', 'description' => 'Roll 6+ to look at a hand.', 'rollRequirement' => 6],
            ['id' => 'c18', 'name' => 'Heavy Fighter', 'type' => 'hero', 'class' => 'fighter', 'description' => 'Roll 8+ to attack with bonus.', 'rollRequirement' => 8]
        ];
    }
}