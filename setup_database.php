<?php

// acesta este scriptul pentru initializarea bazei de date
// incarca autoloader-ul Composer pentru clase
require __DIR__ . '/vendor/autoload.php';

// importa clasele necesare pentru configurare si lucru cu baza de date
use App\Database\DatabaseConfig;
use App\Database\Database;

// afiseaza mesaj de inceput setup
echo "Setting up database...\n";

// obtine configuratia bazei de date folosind clasa DatabaseConfig
try {
    $config = DatabaseConfig::getConfig();
    echo "Database config loaded.\n";
} catch (Exception $e) {
    echo "Error loading database config: " . $e->getMessage() . "\n";
    exit(1);
}

// testeaza conexiunea la baza de date
if (!DatabaseConfig::testConnection()) {
    echo "Cannot connect to database. Please check your configuration.\n";
    exit(1);
}

echo "Database connection successful.\n";

// initializeaza obiectul Database cu configuratia
$db = new Database($config);

// initializeaza repository-urile pentru a crea tabelele si a popula cu date
$gameRepo = new \App\Repository\GameRepository($db);
$playerRepo = new \App\Repository\PlayerRepository($db);
$cardRepo = new \App\Repository\CardRepository($db);
$monsterRepo = new \App\Repository\MonsterRepository($db);

echo "Database tables created and seeded.\n";
echo "Setup complete!\n";