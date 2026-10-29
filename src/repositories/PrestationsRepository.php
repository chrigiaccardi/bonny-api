<?php
// Il file PrestationsRepository conterrà i metodi con le Query SQL da utilizzare per interrogare il DB per le prestazioni

// Inseriamo un namespace per non avere conflitti in futuro e identificare la classe all'interno dell'architettura
namespace Bonny\repositories;

use PDO;

// Creiamo la classe PrestationRepository
class PrestationsRepository {
    // Dichiariamo una proprietà $pdo affinchè ogni PrestationsRepository abbia la sua proprietà privata $pdo
    private PDO $pdo;

    // Creiamo una costante per il nome della tabella così da riutilizzarlo nelle varie funzioni
    private const TABLE_NAME = "prestations";

    /* Con il costruttore dichiariamo che l'oggetto di tipo PDO
     in ingresso sia inserito nella proprietà privata dell'oggetto $PDO */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /* Metodo getAllActive() restituisce tutte le tipologie attive in questo momento: 
        1. Creiamo la query che recupera le prestazioni attive;
        2. Con lo statement prepariamo il PDO a eseguire la query (Sicurezza per SQL Injection);
        3. Allo statement preparato eseguiamo la query;
        4. Recuperiamo i dati trasformandoli in un array associativo come da configurazione PDO (connection.php);
        5. Ritorniamo l'array con tutte le prestazioni attive. */
    function getAllActive(): array {
        $query = "SELECT name, time_saved FROM " . PrestationsRepository::TABLE_NAME . " WHERE active = TRUE";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute();

        $prestationsActive = $stmt->fetchAll();
        return $prestationsActive;
    }

    /* Metodo create() crea una nuova tipologia di prestazione inserendo name e time_saved e restituisce l'id della prestazione creata in formato INT:
        1. Creiamo la query che inserisce i dati nella tabella specifica, nelle colonne specifiche i valori dinamici in ingresso nella funzione;
        2. Prepariamo lo stmt con la query;
        3. Con bindValue() andiamo a collegare i placeholder con i valori in ingresso della funzione. Aggiungiamo anche delle specifiche,
            dichiarando che nome deve essere un parametro stringa e invece il parametro time_saved un int;
        4. Eseguiamo la lo statement - query con execute;
        5. Una volta eseguito lo stmt recuperiamo l'ultimo id inserito all'interno della tabella;
        6. Ritorniamo l'id_prestation tipizzato INT all'inizio ():int*/
    function create(string $name, int $time_saved): int {
        $query = "INSERT INTO " . PrestationsRepository::TABLE_NAME . " (name, time_saved) VALUES (:name, :time_saved)";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':time_saved', $time_saved, PDO::PARAM_INT);
        $stmt->execute();

        $id_prestation = (int) $this->pdo->lastInsertId();
        return $id_prestation;
    }

    /* Metodo update($id): Aggiorna totalmente con PUT una prestazione specificata dall'$id in ingresso, 
        con i valori $name e $time_saved arrivati dalla richiesta:
        1. Creiamo la query che aggiorna la prestazione in base all'id in ingresso e ai dati ricevuti dalla richiesta;
        2. Prepariamo lo statement, effettuaimo il bindValue e eseguiamo lo statement;
        3. Ritorniamo indietro il conteggio delle righe modificate con la query se 1 vuol dire che la modifica è stata effettuata,
            quindi è presente un record, 0 invece se è uguale a prima e non vi sono record. */
    function update(int $id_prestation, string $name, int $time_saved):int {
        $query = "UPDATE " . PrestationsRepository::TABLE_NAME . " SET name = :name, time_saved = :time_saved WHERE id = :id";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':time_saved', $time_saved, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id_prestation, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->rowCount();
        return $row;
    }

    /* Metodo deactivate($id_pprestation), Ho deciso di disattivare la prestazione e non eliminarla completamente per una questione
        di storicità di dati. Se vado ad eliminare una prestazione e a cascata i dettagli di una vendita, di conseguenza 
        il tempo rispamriato totale dai cittadini verrà modificato e non è coerente:
        1. Creiamo la query che disattiva il record selezionato con l'id;
        2. Prepariamo lo statement, effettuaimo il bindValue e eseguiamo lo statement;
        3. Ritorniamo indietro il numero di record presenti cioè modificati.*/
    function deactivate(int $id_prestation):int {
        $query = "UPDATE " . PrestationsRepository::TABLE_NAME . " SET active = FALSE WHERE id = :id";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':id', $id_prestation, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->rowCount();
        return $row;
    }

    /* Metodo Activate($id_prestation), Di conseguenza alla disattivazione della prestazione dobbiamo anche dare
        la possibilità di riattivarla nel caso di necessità:
        1. Creiamo la query che attiva il record selezionato con l'id;
        2. Prepariamo lo statement, effettuaimo il bindValue e eseguiamo lo statement;
        3. Ritorniamo indietro il numero di righe modificate. */
    function activate(int $id_prestation): int {
        $query = "UPDATE " . PrestationsRepository::TABLE_NAME . " SET active = TRUE WHERE id = :id";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':id', $id_prestation, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->rowCount();
        return $row;
    }

    /* Creiamo il metodo existsById() per verificare tramite query che l'id_prestation esista nella tabella:
        1. Creiamo la query che cerca il record giusto tramite l'id;
        2. Prepariamo lo statement, effettuaimo il bindValue e eseguiamo lo statement;
        3. fetchColumn() restituisce 1/0 in base alla presenza, con (bool) lo trasformiamo in true/false;
        4. Ritorniamo il risultato. */
    function existsById(int $id_prestation): bool {
        $query = "SELECT EXISTS (SELECT 1 FROM " . PrestationsRepository::TABLE_NAME . " WHERE id = :id)";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':id', $id_prestation, PDO::PARAM_INT);
        $stmt->execute();

        $booleanResult = (bool) $stmt->fetchColumn();
        return $booleanResult;
    }
}
