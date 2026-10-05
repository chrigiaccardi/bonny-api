<?php
// Il file PrestationsRepository conterrà i metodi con le Query SQL da utilizzare per interrogare il DB per le prestazioni

// Inseriamo un namespace per non avere conflitti in futuro e identificare la classe all'interno dell'architettura
namespace Bonny\repositories;

use PDO;

// Creiamo la classe PrestationRepository
class PrestationsRepository {
    // istanziamo la variabile $pdo affinchè ogni PrestationsRepository abbia la sua proprietà privata $pdo
    private PDO $pdo;

    // Creiamo una variabile per il nome della tabella
    private const TABLE_NAME = "prestations";

    /* Con il costruttore dichiariamo che l'oggetto di tipo PDO
     in ingresso sia inserito nella proprietà privata dell'oggetto $PDO */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Metodo getAllActive() restituisce tutte le tipologie attive in questo momento.
    function getAllActive(): array {
        // Creiamo la query che recupera le prestazioni attive
        $query = "SELECT name, time_saved FROM " . PrestationsRepository::TABLE_NAME . " WHERE active = TRUE";

        // Con lo statement prepariamo il PDO a eseguire la query (Sicurezza per SQL Injection)
        $stmt = $this->pdo->prepare($query);
        // A statement preparato eseguiamo la query
        $stmt->execute();

        // Recuperiamo i dati trasformandoli in un array associativo come da configurazione PDO (connection.php)
        $prestations = $stmt->fetchAll();

        // Ritorniamo l'array con tutte le prestazioni attive
        return $prestations;
    }
}
