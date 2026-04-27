<?php

namespace App\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;

// aceasta clasa gestioneaza conexiunea la baza de date folosind Doctrine DBAL
// ofera metode pentru executarea de query-uri si statement-uri in baza de date
class Database
{
    private Connection $connection;

    // aceasta functie initializeaza clasa cu configuratia bazei de date
    // primeste un array de configuratie si creeaza conexiunea folosind DriverManager din Doctrine
    public function __construct(array $config)
    {
        $this->connection = DriverManager::getConnection($config);
    }

    // aceasta functie returneaza conexiunea la baza de date
    // returneaza instanta Connection pentru a fi folosita direct daca e necesar
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    // aceasta functie executa un query SELECT si returneaza rezultatele
    // primeste sql-ul si parametrii executa query-ul si returneaza toate randurile ca array asociativ sau arunca exceptie daca esueaza
    public function executeQuery(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->connection->executeQuery($sql, $params);
            return $stmt->fetchAllAssociative();
        } catch (Exception $e) {
            throw new \RuntimeException('Database query failed: ' . $e->getMessage());
        }
    }

    // aceasta functie executa un statement INSERT UPDATE sau DELETE si returneaza numarul de randuri afectate
    // primeste sql-ul si parametrii executa statement-ul si returneaza numarul de randuri afectate sau arunca exceptie daca esueaza
    public function executeStatement(string $sql, array $params = []): int
    {
        try {
            return $this->connection->executeStatement($sql, $params);
        } catch (Exception $e) {
            throw new \RuntimeException('Database statement failed: ' . $e->getMessage());
        }
    }

    // aceasta functie returneaza ultimul id inserat
    // apeleaza metoda lastInsertId din conexiune pentru a obtine id-ul ultimului rand inserat
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }
}