<?php
// Il file config ha il compito di caricare e prendere i dati di configurazione dal file .env

// Importiamo la Classe Dotenv
use Dotenv\Dotenv;

/* Creiamo il collegamento e la lettura del file .env utilizzando la classe Dotenv;
dirname otiene la directory padre del percorso passato, quindi due volte arriva alla root del progetto
*/
$dotEnv = Dotenv::createImmutable(dirname(dirname(__DIR__)));

// Carichiamo il file .env per darci la possibilità di leggerlo
$dotEnv->load();

// Ritorniamo l'array associativo database con tutti i dati di connessione presenti nel file .env
return [
    'database' => [
        'connection' => $_ENV['DB_CONNECTION'],
        'host' => $_ENV['DB_HOST'],
        'port' => $_ENV['DB_PORT'],
        'name' => $_ENV['DB_NAME'],
        'user' => $_ENV['DB_USER'],
        'password' => $_ENV['DB_PASSWORD'],
    ]
];
