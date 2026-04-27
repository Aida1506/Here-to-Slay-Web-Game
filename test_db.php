<?php

// acesta este scriptul de testare pentru conexiunea la baza de date
// incarca autoloader-ul pentru clase
require 'vendor/autoload.php';

// incearca sa execute testul si prinde exceptiile
try {
    // obtine configuratia bazei de date folosind DatabaseConfig
    $config = \App\Database\DatabaseConfig::getConfig();
    echo "Database config:\n";
    // afiseaza configuratia cu print_r
    print_r($config);

    echo "\nTesting connection...\n";
    // testeaza conexiunea folosind metoda testConnection
    $connected = \App\Database\DatabaseConfig::testConnection();
    echo "Connection: " . ($connected ? "SUCCESS" : "FAILED") . "\n";
} catch (Exception $e) {
    // prinde exceptiile si afiseaza mesajul de eroare
    echo "Error: " . $e->getMessage() . "\n";
}