<?php
// Il file connection ha il compito di utilizzare i dati di configurazione per creare e restituire la connessione PDO al DB

// Inseriamo un namespace per non avere conflitti in futuro e identificare la classe all'interno dell'architettura
namespace Bonny\database;

use PDO;

// Così facendo il nome completo della classe è Bonny\database\Connection
class Connection {

    // istanziamo la variabile $db affinchè ogni Connection abbia la sua proprietà privata $db
    private $db;

    /* Il costruttore permette di mettere l'oggetto parametro in ingresso nella proprietà privata $db dell'oggetto corrente.
    Quindi quando verrà chiamata una nuova classe new Connection ($config['database']) i dati di configurazione verranno
    inseriti automaticamente nella proprietà privata $db all'interno della classe */
    public function __construct($db){
        $this->db = $db;
    }

    // Creiamo la funzione di connessione getConnection
    function getConnection() /*: PDO*/ {
        $dsn = 'mysql:host=' . $this->db['host'] . ';port=' . $this->db['port'] . ';dbname=' . $this->db['name'] . ';charset=utf8mb4';
    }
}

// Importiamo il file config.php per utilizzare l'array di configurazione
$db = $config['database'];
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
