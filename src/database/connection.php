<?php
// Il file connection ha il compito di utilizzare i dati di configurazione per creare e restituire la connessione PDO al DB

// Inseriamo un namespace per non avere conflitti in futuro e identificare la classe all'interno dell'architettura
namespace Bonny\database;

// Utilizziamo PDO come connessione tra PHP e il Server Database
use PDO;

// Così facendo il nome completo della classe è Bonny\database\Connection
class Connection
{

    // istanziamo la variabile $db affinchè ogni Connection abbia la sua proprietà privata $db
    private array $db;

    /* Il costruttore permette di mettere l'oggetto parametro in ingresso nella proprietà privata $db dell'oggetto corrente.
    Quindi quando verrà chiamata una nuova classe new Connection ($config['database']) i dati di configurazione verranno
    inseriti automaticamente nella proprietà privata $db all'interno della classe */
    public function __construct(array $db)
    {
        $this->db = $db;
    }

    /* Creiamo la funzione di connessione getConnection:
        1. Il dsn (Data Source Name) è una stringa che contiene le informazioni per connettere l'app ad un DB; 
        2. Creiamo la connessione PDO inserendo il dsn, l'user e la password del DB, Aggiungiamo due opzioni:
            per gli errori trasformarle in eccezzioni così che possano propagarsi verso l'alto;
            che i dati restutiuti da PDO siano restituiti in formato Array Associativo.
        3. Ritorniamo $pdo come promesso all'inizio con ():PDO. */
    function getConnection(): PDO
    {
        $dsn = 'mysql:host=' . $this->db['database']['host'] . ';port=' . $this->db['database']['port'] . ';dbname=' . $this->db['database']['name'] . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $this->db['database']['user'], $this->db['database']['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    }
}
