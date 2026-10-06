<?php
// Il file PrestationsRepository conterrà i metodi con le Query SQL da utilizzare per interrogare il DB per le prestazioni

// Inseriamo un namespace per non avere conflitti in futuro e identificare la classe all'interno dell'architettura
namespace Bonny\repositories;

use PDO;

// Creiamo la classe PrestationRepository
class PrestationsRepository {
    // istanziamo la variabile $pdo affinchè ogni PrestationsRepository abbia la sua proprietà privata $pdo
    private PDO $pdo;

    // Creiamo una variabile per il nome della tabella così da riutilizzarlo nelle varie funzioni
    private const TABLE_NAME = "prestations";

    /* Con il costruttore dichiariamo che l'oggetto di tipo PDO
     in ingresso sia inserito nella proprietà privata dell'oggetto $PDO */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Metodo getAllActive(): restituisce tutte le tipologie attive in questo momento.
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

    // Metodo create(): crea una nuova tipologia di prestazione inserendo name e time_saved e restituisce l'id della prestazione creata in formato INT
    function create(string $name, int $time_saved): int {
        // Creiamo la query che inserisce i dati nella tabella specifica, nelle colonne specifiche i valori dinamici in ingresso nella funzione
        $query = "INSERT INTO " . PrestationsRepository::TABLE_NAME . " (name, time_saved) VALUES (:name, :time_saved)";

        // Prepariamo lo stmt con la query
        $stmt = $this->pdo->prepare($query);

        /* Con bindValue() andiamo a collegare i placeholder con i valori in ingresso della funzione.
        Aggiungiamo anche delle specifiche, dichiarando che nome deve essere un parametro stringa e
        Invece il parametro time_saved un int */
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':time_saved', $time_saved, PDO::PARAM_INT);

        // Eseguiamo la lo statement - query con execute
        $stmt->execute();

        // Una volta eseguito lo stmt recuperiamo l'ultimo id inserito all'interno della tabella
        $id_prestation = (int) $this->pdo->lastInsertId();

        // Ritorniamo l'id_prestation tipizzato INT all'inizio ():int
        return $id_prestation;
    }
}
