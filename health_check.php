<?php

// acesta este scriptul de verificare a sanatatii aplicatiei
// verifica versiunea PHP extensiile baza de date si fisierele
echo "Here to Slay API Health Check\n";
echo "==============================\n\n";

// verifica versiunea PHP
echo "PHP Version: " . PHP_VERSION . "\n";

// verifica extensiile PHP necesare
$requiredExtensions = ['pdo', 'json', 'mbstring'];
foreach ($requiredExtensions as $ext) {
    echo "Extension {$ext}: " . (extension_loaded($ext) ? '✓' : '✗') . "\n";
}

// sectiunea de verificare a bazei de date
echo "\nDatabase Check:\n";
try {
    // incarca autoloader-ul
    require __DIR__ . '/vendor/autoload.php';
    // obtine configuratia bazei de date
    $config = \App\Database\DatabaseConfig::getConfig();
    // testeaza conexiunea
    $connected = \App\Database\DatabaseConfig::testConnection();
    echo "Database Connection: " . ($connected ? '✓' : '✗') . "\n";
    echo "Database Type: " . ($config['driver'] ?? 'unknown') . "\n";
} catch (Exception $e) {
    // prinde erorile si le afiseaza
    echo "Database Error: " . $e->getMessage() . "\n";
}

// verifica existenta fisierelor importante
$files = [
    'vendor/autoload.php',
    'src/Database/Database.php',
    'src/Service/GameService.php',
    'public/index.php'
];

echo "\nFile Check:\n";
foreach ($files as $file) {
    echo "File {$file}: " . (file_exists($file) ? '✓' : '✗') . "\n";
}

echo "\nSetup complete! Access the API at http://localhost/proiectpw/\n";