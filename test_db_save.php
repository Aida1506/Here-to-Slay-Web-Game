<?php

// acesta este scriptul de testare pentru salvarea in baza de date
// incarca autoloader-ul pentru clase
require 'vendor/autoload.php';

// importa clasele pentru configurare si repository-uri
use App\Database\DatabaseConfig;
use App\Repository\GameRepository;
use App\Repository\PlayerRepository;
use App\Repository\CardRepository;
use App\Repository\MonsterRepository;

// incearca sa execute testul si prinde exceptiile
try {
    // obtine configuratia bazei de date
    $config = DatabaseConfig::getConfig();
    // initializeaza conexiunea la baza de date
    $db = new App\Database\Database($config);
    echo 'Database connected' . PHP_EOL;

    // initializeaza repository-urile pentru joc jucatori carti si monstri
    $gameRepo = new GameRepository($db);
    $playerRepo = new PlayerRepository($db);
    $cardRepo = new CardRepository($db);
    $monsterRepo = new MonsterRepository($db);
    echo 'Repositories created' . PHP_EOL;

    // creeaza un joc de test cu date de exemplu
    $game = [
        'id' => 'test_game_' . time(),
        'name' => 'Test Game',
        'currentTurnPlayerId' => 'p1',
        'actionPoints' => 3,
        'mainDeck' => [],
        'discardPile' => [],
        'monsterDeck' => [],
        'activeMonsters' => [],
        'lastMessage' => 'Test'
    ];

    // salveaza jocul in baza de date
    $gameRepo->save($game);
    echo 'Game saved' . PHP_EOL;

    // creeaza un jucator de test cu date de exemplu
    $player = [
        'id' => 'test_player_' . time(),
        'name' => 'Test Player',
        'partyLeader' => 'Test Leader',
        'partyLeaderClass' => 'fighter',
        'hand' => [],
        'party' => [],
        'slainMonsters' => [],
        'gameId' => $game['id'],
        'role' => 'player'
    ];

    // salveaza jucatorul in baza de date
    $playerRepo->save($player);
    echo 'Player saved' . PHP_EOL;

    echo 'Test successful' . PHP_EOL;
} catch (Exception $e) {
    // prinde exceptiile si afiseaza mesajul de eroare
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    echo 'File: ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
}