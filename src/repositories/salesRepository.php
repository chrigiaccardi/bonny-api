<?php
/* Il file salesRepository conterrà i metodi con le query SQL da utilizzare 
per interrogare il DB per le vendite */

namespace Bonny\repositories;

use InvalidArgumentException;
use PDO;
use Throwable;

// Creiamo la classe salesRepository
class SalesRepository {
    // Dichiariamo $pdo.
    private PDO $pdo;

    // Creiamo una costante per il nome della tabella così da riutilizzarla
    private const TABLE_SALES = "sales";
    private const TABLE_SALES_DETAILS = "sales_details";
    private const TABLE_PRESTATIONS = "prestations";

    // Usiamo il costruttore per eguagliare il parametro in ingresso con la variabile privata
    public function __construct(PDO $pdo){
        $this->pdo = $pdo;
    }

    // Metodo getAllSales restituisce tutte le vendite effettuate
    function getAll(): array {
        /* Query SQL: 
        La query utilizza gli alias per accorciare la nomenclatura delle tabella;
        Utilizziamo inner join per effettuare collegamenti relazionali tra diverse tabelle*/
        $query = "SELECT s.id, s.sale_date, p.name, sd.quantity 
            FROM " . SalesRepository::TABLE_SALES . " s 
            INNER JOIN sales_details sd ON s.id = sd.id_sale 
            INNER JOIN prestations p ON p.id = sd.id_prestation";

        // Prepariamo lo statement e lo eseguiamo
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();

        // Recuperiamo i dati come array associativo
        $allSales = $stmt->fetchAll();

        // Ritorniamo l'array con tutte le vendite
        return $allSales;
    }

    /* Metodo create: il metodo create è più complicato visto che inserisce
    dati in due tabelle differenti:
        1. La vendita viene creata quindi di conseguenza l'id e viene impostata in automatico la data corrente;
        2. Il file json che riceve ha semplicemente un array con le tipologie di prestazioni e le loro quantità;
        3. Si estraggono il'id_prestation e la quantità da ogni dettaglio con un foreach e si recupera per ogni il time_saved, salvato di conseguenza nel time_saved_snapshot.
        4. Si esegue la query per inserire i dettagli della vendita collegati all'id della vendita
        5. Concluso si ritorna l'id della vendita appena creata 
        5.1 Se vi sono errori o eccezioni vengono rilanciate e gestite poi dal service. */
    function create(array $details){
        try {
            // Facciamo partire la transaction: permette di eseguire un gruppo di query SQL in una unica soluzione
        $this->pdo->beginTransaction();

        // Query SQL per inserire una nuova vendita e la data corrente
        $querySale = "INSERT INTO " . SalesRepository::TABLE_SALES . " (sale_date) VALUES (CURRENT_DATE)";

        // Prepariamo lo statement e lo eseguiamo
        $stmt = $this->pdo->prepare($querySale);
        $stmt->execute();

        // Una volta eseguito lo stmt recuperiamo l'ultimo id inserito all'interno della tabella
        $newIdSale = (int) $this->pdo->lastInsertId();

        // Query SQL per inserire i dettagli della vendita
        $querySaleDetails = "INSERT INTO " . SalesRepository::TABLE_SALES_DETAILS . " (id_sale, id_prestation, quantity, time_saved_snapshot)
            VALUES (:id_sale, :id_prestation, :quantity, :time_saved_snapshot)";

        // Prepariamo lo stmt con la query
        $stmt = $this->pdo->prepare($querySaleDetails);
        
        /* Eseguiamo un foreach, per ogni detail dell'array details in ingresso: 
            1. Estraiamo l'id_prestation e la quantity;
            2. Recuperiamo il time_saved della prestazione specifica dalla tabella prestations
            3. Se l'id_prestation inserito non esiste allora rollback e creazione nuova eccezione
            4. colleghiamo i parametri dinamici alla query;
            5. Eseguiamo lo stmt */

        foreach ($details as $detail){
            $id_prestation = $detail['id_prestation'];
            $quantity = $detail['quantity'];

            // Dobbiamo recuperare il time_saved dalla tabella prestation tramite l'id_prestation estratto
            $queryTimeSaved = "SELECT time_saved FROM " . SalesRepository::TABLE_PRESTATIONS . " WHERE id = :id AND active = true";
            $stmtTimeSaved = $this->pdo->prepare($queryTimeSaved);
            $stmtTimeSaved->bindValue(':id', $id_prestation, PDO::PARAM_INT);
            $stmtTimeSaved->execute();
            // Con fetchColumn estraiamo il valore dell'unica riga dell'unica colonna
            $time_saved_snapshot = $stmtTimeSaved->fetchColumn();

            /* Utilizziamo ... === false perchè lo 0 è falsy ma valido per le regole di dominio di time_saved.
            Mandiamo un'eccezione che la prestazione abbinata a quell'id non esiste.
            InvalidArgumentException fa saltare direttamente al catch senza eseguire più nulla,
            Così facendo si attiva il rollBack visto che la transaction è ancora attiva e la disattiva. */
            if ($time_saved_snapshot === false) {
                throw new InvalidArgumentException("La prestazione " . $id_prestation . " non esiste");
            }
            
            // Con bindValue() andiamo a collegare i placeholder con i valori in ingresso della funzione.
            $stmt->bindValue(':id_sale', $newIdSale, PDO::PARAM_INT);
            $stmt->bindValue(':id_prestation', $id_prestation, PDO::PARAM_INT);
            $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
            $stmt->bindValue(':time_saved_snapshot', $time_saved_snapshot, PDO::PARAM_INT);

            // Eseguiamo lo statement - query con execute
            $stmt->execute();
        }
        
        // Chiudiamo la transaction con commit() ed esegue tutto insieme
        $this->pdo->commit();

        // Ritorniamo l'id dell'ultima vendita avvenuta
        return $newIdSale;

        } catch ( Throwable $error) {
            // Se la transizione è ancora attiva la interrompiamo e mandiamo vari errori o eccezzioni al service che li gestirà
            if($this->pdo->inTransaction()){
                $this->pdo->rollBack();
            }
            throw $error;
        }
    } 
}