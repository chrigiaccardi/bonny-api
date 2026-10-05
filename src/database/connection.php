<?php
// Il file Database ha il compito di utilizzare i dati di configurazione per creare e restituire la connessione PDO al DB

// Importiamo il file config.php per utilizzare l'array di configurazione
require_once '../Config/config.php';

class Connection {
    public $conn;

    function getConnection () {
        /* Creiamo il dsn (Data Source Name) che è una stringa che da a PDO le informazioni del db per comunicarci,
         il chartset supporta tutti i caratteri. */
        $dsn = 'mysql:host=' .  . ';dbname=' . DB_NAME . ';charset=utf8mb4';

        // Tentiamo la connessione al PDO con i dati di autenticazione del DB
        try{
            $pdo = new PDO($dsn, DB_USER, DB_PASSWORD)
        };

};
}

function getConnection(): PDO {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return $pdo;

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed.']);
        exit;
    }
}
