<?php
// Il file connection ha il compito di utilizzare i dati di configurazione per creare e restituire la connessione PDO al DB

// Inseriamo un namespace per non avere conflitti in futuro e identificare la classe all'interno dell'architettura
namespace Bonny\database;

// Utilizziamo PDO come connessione tra PHP e il Server Database
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
    function getConnection(): PDO {
        // Il dsn (Data Source Name) è una stringa che contiene le informazioni per connettere l'app ad un DB
        $dsn = 'mysql:host=' . $this->db['host'] . ';port=' . $this->db['port'] . ';dbname=' . $this->db['name'] . ';charset=utf8mb4';

        /* Creiamo la connessione PDO inserendo il dsn, l'user e la password del DB.
        Aggiungiamo due opzioni, per gli errori trasformarle in eccezzioni così che possano propagarsi verso l'alto,
        e che i dati restutiuti da PDO siano restituiti in formato Array Associativo */
        $pdo = new PDO($dsn, $this->db['user'], $this->db['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        // Ritornismo $pdo come promesso all'inizio ():PDO
        return $pdo;
    }
}
