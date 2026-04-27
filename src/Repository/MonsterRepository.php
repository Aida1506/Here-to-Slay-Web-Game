<?php

namespace App\Repository;

use App\Database\Database;

// aceasta clasa gestioneaza operatiunile cu monstrii in baza de date
// foloseste clasa Database pentru a interactiona cu baza de date si ofera metode pentru a gasi monstri
class MonsterRepository
{
    private Database $db;

    // aceasta functie initializeaza repository-ul cu baza de date
    // primeste instanta Database ca parametru o atribuie proprietatii private apoi creeaza tabela si adauga datele initiale daca e necesar
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->createTable();
        $this->seedMonsters();
    }

    // aceasta functie privata creeaza tabela monsters daca nu exista
    // executa un query SQL pentru a crea tabela cu coloanele id name roll_requirement penalty reward
    private function createTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS monsters (
                id VARCHAR(255) PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                roll_requirement INT NOT NULL,
                penalty TEXT,
                reward TEXT
            )
        ";

        $this->db->executeStatement($sql);
    }

    // aceasta functie privata adauga monstrii default in tabela daca e goala
    // verifica daca exista monstri in tabela daca nu parcurge monstrii default si ii insereaza in baza de date
    private function seedMonsters(): void
    {
        $count = (int) $this->db->executeQuery("SELECT COUNT(*) as count FROM monsters")[0]['count'];

        if ($count > 0) {
            return;
        }

        foreach ($this->getDefaultMonsters() as $monster) {
            $this->db->executeStatement(
                "INSERT INTO monsters (id, name, roll_requirement, penalty, reward) VALUES (?, ?, ?, ?, ?)",
                [
                    $monster['id'],
                    $monster['name'],
                    $monster['rollRequirement'],
                    $monster['penalty'],
                    $monster['reward']
                ]
            );
        }
    }

    // aceasta functie returneaza toti monstrii din baza de date
    // executa un query pentru a selecta toti monstrii ordonati dupa id apoi aplica hydrateMonster pentru fiecare rand
    public function findAll(): array
    {
        $rows = $this->db->executeQuery("SELECT * FROM monsters ORDER BY id ASC");
        return array_map([$this, 'hydrateMonster'], $rows);
    }

    // aceasta functie privata transforma un rand din baza de date intr-un array de monstru
    // primeste randul din baza de date si returneaza un array cu id name rollRequirement convertit la int penalty reward
    private function hydrateMonster(array $row): array
    {
        return [
            'id' => $row['id'],
            'name' => $row['name'],
            'rollRequirement' => (int) $row['roll_requirement'],
            'penalty' => $row['penalty'] ?? '',
            'reward' => $row['reward'] ?? ''
        ];
    }

    // aceasta functie privata returneaza monstrii default pentru initializare
    // returneaza un array cu 6 monstri fiecare continand id name rollRequirement penalty reward
    private function getDefaultMonsters(): array
    {
        return [
            ['id' => 'm1', 'name' => 'Dark Bat', 'rollRequirement' => 7, 'penalty' => 'Sacrifice a hero.', 'reward' => 'Draw two cards.'],
            ['id' => 'm2', 'name' => 'Forest Beast', 'rollRequirement' => 8, 'penalty' => 'Discard two cards.', 'reward' => 'Gain +1 on attacks.'],
            ['id' => 'm3', 'name' => 'Crystal Dragon', 'rollRequirement' => 9, 'penalty' => 'Destroy one item.', 'reward' => 'Draw one card.'],
            ['id' => 'm4', 'name' => 'Ancient Slime', 'rollRequirement' => 6, 'penalty' => 'Discard one card.', 'reward' => 'You may reroll once.'],
            ['id' => 'm5', 'name' => 'Storm Wolf', 'rollRequirement' => 8, 'penalty' => 'Sacrifice a hero.', 'reward' => 'Play one extra hero.'],
            ['id' => 'm6', 'name' => 'Shadow Giant', 'rollRequirement' => 10, 'penalty' => 'Discard your hand.', 'reward' => 'Count as one slain monster.']
        ];
    }
}