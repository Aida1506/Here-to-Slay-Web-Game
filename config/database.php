<?php

// acesta este fisierul de configurare pentru baza de date
// returneaza un array cu setarile pentru Doctrine DBAL
return [
    // driver-ul folosit pentru conexiune pdo_sqlite pentru SQLite
    'driver' => 'pdo_sqlite',
    // calea catre fisierul bazei de date SQLite in directorul storage
    'path' => __DIR__ . '/../storage/database.sqlite',
];
