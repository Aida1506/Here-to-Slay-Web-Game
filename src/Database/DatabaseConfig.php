<?php

namespace App\Database;

// aceasta clasa gestioneaza configuratia bazei de date
// detecteaza automat baza de date disponibila si salveaza configuratia
class DatabaseConfig
{
    // aceasta functie returneaza configuratia bazei de date
    // verifica daca exista fisierul de configuratie si daca driverul e suportat altfel detecteaza baza de date si salveaza configuratia
    public static function getConfig(): array
    {
        $configFile = __DIR__ . '/../../config/database.php';

        if (file_exists($configFile)) {
            $config = require $configFile;
            if (self::driverExists($config['driver'] ?? '')) {
                return $config;
            }
        }

        $config = self::detectDatabase();
        self::saveConfig($config);
        return $config;
    }

    // aceasta functie detecteaza automat baza de date disponibila
    // verifica daca e disponibila SQLite apoi MySQL altfel arunca exceptie
    public static function detectDatabase(): array
    {
        if (extension_loaded('pdo_sqlite')) {
            return [
                'driver' => 'pdo_sqlite',
                'path' => __DIR__ . '/../../storage/database.sqlite'
            ];
        }

        if (extension_loaded('pdo_mysql')) {
            return self::mysqlConfig();
        }

        throw new \RuntimeException('No supported database driver found. Please install PDO MySQL or SQLite.');
    }

    // aceasta functie salveaza configuratia in fisier
    // creeaza directorul config daca nu exista si scrie configuratia in database.php
    public static function saveConfig(array $config): void
    {
        $configDir = __DIR__ . '/../../config';

        if (!is_dir($configDir)) {
            mkdir($configDir, 0755, true);
        }

        file_put_contents($configDir . '/database.php', "<?php\n\nreturn " . var_export($config, true) . ";\n");
    }

    // aceasta functie testeaza conexiunea la baza de date
    // incearca sa execute un query simplu si returneaza true daca reuseste false daca esueaza
    public static function testConnection(): bool
    {
        try {
            (new Database(self::getConfig()))->executeQuery('SELECT 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    // aceasta functie privata verifica daca driverul e suportat
    // foloseste match pentru a verifica daca driverul e pdo_sqlite sau pdo_mysql si daca extensia e incarcata
    private static function driverExists(string $driver): bool
    {
        return match ($driver) {
            'pdo_sqlite' => extension_loaded('pdo_sqlite'),
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            default => false
        };
    }

    // aceasta functie privata returneaza configuratia pentru MySQL
    // seteaza configuratia standard pentru MySQL si creeaza baza de date heretoslay daca nu exista apoi adauga dbname la config
    private static function mysqlConfig(): array
    {
        $config = [
            'driver' => 'pdo_mysql',
            'host' => 'localhost',
            'port' => 3306,
            'user' => 'root',
            'password' => '',
            'charset' => 'utf8mb4'
        ];

        $pdo = new \PDO('mysql:host=localhost;port=3306;charset=utf8mb4', 'root', '');
        $pdo->exec('CREATE DATABASE IF NOT EXISTS heretoslay');

        return $config + ['dbname' => 'heretoslay'];
    }
}
