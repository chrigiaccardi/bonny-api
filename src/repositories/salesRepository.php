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
    function getAll():array {
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

    // Metodo getById() restituisce la vendita selezionata tramite l'id
    function getById(int $id_sale):array {
        // Query SQL
        $query = "SELECT s.id, s.sale_date, p.name, sd.quantity
        FROM " . SalesRepository::TABLE_SALES . " s 
        INNER JOIN " . SalesRepository::TABLE_SALES_DETAILS . " sd ON s.id = sd.id_sale
        INNER JOIN " . SalesRepository::TABLE_PRESTATIONS . " p ON p.id = sd.id_prestation
        WHERE s.id = :id_sale";

        // Prepariamo lo statement, eseguiamo il bindValue e lo eseguiamo
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':id_sale', $id_sale, PDO::PARAM_INT);
        $stmt->execute();

        /* Recuperiamo i dati con un array associativo: può essere che ci sia solo una riga 
        per una singola vendita con una prestazione oppure più righe, quindi più prestazioni
        per la singola vendita.
        Nel caso che l'id non esista fetchAll() restituisce un array vuoto*/
        $sale = $stmt->fetchAll();

        // Ritorniamo la vendita selezionata
        return $sale;
    }

    /* Metodo create(): il metodo create è più complicato visto che inserisce
    dati in due tabelle differenti:
        1. La vendita viene creata quindi di conseguenza l'id e viene impostata in automatico la data corrente;
        2. Il file json che riceve ha semplicemente un array con le tipologie di prestazioni e le loro quantità;
        3. Si estraggono il'id_prestation e la quantità da ogni dettaglio con un foreach e si recupera per ogni il time_saved, salvato di conseguenza nel time_saved_snapshot.
        4. Si esegue la query per inserire i dettagli della vendita collegati all'id della vendita
        5. Concluso si ritorna l'id della vendita appena creata 
        5.1 Se vi sono errori o eccezioni vengono rilanciate e gestite poi dal service. */
    function create(array $details):int {
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

            // Ritorniamo l'id dell'ultima vendita inserita
            return $newIdSale;

        } catch ( Throwable $error) {
            // Se la transizione è ancora attiva la interrompiamo e mandiamo vari errori o eccezzioni al service che li gestirà
            if($this->pdo->inTransaction()){
                $this->pdo->rollBack();
            }
            throw $error;
        }
    } 

    /* Metodo delete(): elimina la vendita selezionata e di conseguenza in cascata
        anche i dettagli correlati */
    function delete(int $id_sale):int {
        // Query SQL
        $query = "DELETE FROM " . SalesRepository::TABLE_SALES . " WHERE id = :id_sale";
        
        // Prepariamo lo statement, eseguiamo il bindValue e lo eseguiamo
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':id_sale', $id_sale, PDO::PARAM_INT);
        $stmt->execute();

        /* Eseguiamo una conferma che effettivamente la vendita sia stata eseguita 
        se $row 1 allora è stata eseguita, se 0 allora l'id è inesistente e non
        è stata eliminata nessuna riga*/
        $deletedRow = $stmt->rowCount();

        // Ritorniamo il record della vendita eliminata
        return $deletedRow;
    }

    /* Metodo updateDetails(): aggiunge 1 o + dettagli ad una vendita esistente 
    La logica sarà simile a quella del metodo create():
        1. Verificare che la vendita esista.
        2. Avviare una transazione.
        3. Per ogni dettaglio, recuperare il time_saved della prestazione attiva.
        4. Inserire il dettaglio collegandolo alla vendita esistente e salvando time_saved_snapshot.
        5. Eseguire commit() se tutto va bene; in caso di errore, rollback(). */
    function updateDetails(int $id_sale, array $details):int {
        try {
            // Effetuiamo una verifica che la vendita effettivamente esista tramite l'id
            $querySale = "SELECT id FROM " . SalesRepository::TABLE_SALES . " WHERE id = :id";
            $stmtSale = $this->pdo->prepare($querySale);
            $stmtSale->bindValue(':id', $id_sale, PDO::PARAM_INT);
            $stmtSale-> execute();
            $checkSale = count($stmtSale->fetchAll());

            // Se la vendita non esiste ritorniamo subito senza fare la transaction
            if ($checkSale === 0) {
                return $checkSale;
            } else {
                // Facciamo partire la transaction: permette di eseguire un gruppo di query SQL in una unica soluzione
                $this->pdo->beginTransaction();

                // Query SQL per inserire i dettagli della vendita
                $querySaleDetails = "INSERT INTO " . SalesRepository::TABLE_SALES_DETAILS . " (id_sale, id_prestation, quantity, time_saved_snapshot)
                    VALUES (:id_sale, :id_prestation, :quantity, :time_saved_snapshot)";

                // Prepariamo lo stmt con la query
                $stmt = $this->pdo->prepare($querySaleDetails);

                /* Per ogni dettaglio in dettagli estraiamo id_prestation e quantity;
                per ogni id_prestation andiamo a recuperare il time_saved per inserirlo nel time_saved_snapshot;
                Eseguiamo i bindValue per la query e la eseguiamo */
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
                    $stmt->bindValue(':id_sale', $id_sale, PDO::PARAM_INT);
                    $stmt->bindValue(':id_prestation', $id_prestation, PDO::PARAM_INT);
                    $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
                    $stmt->bindValue(':time_saved_snapshot', $time_saved_snapshot, PDO::PARAM_INT);

                    // Eseguiamo lo statement - query con execute
                    $stmt->execute();
                }
                // Chiudiamo ed eseguiamo la transaction con commit
                $this->pdo->commit();

                //Ritorniamo 1 per confermare la corretta esecuzione dell'aggiornamento o 0 se la vendita non esiste
                return 1;
            }

        } catch ( Throwable $error) {
            // Se la transizione è ancora attiva la interrompiamo e mandiamo vari errori o eccezzioni al service che li gestirà
            if($this->pdo->inTransaction()){
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    // Metodo deleteDetail: eliminare un dettaglio specifico di una specifica vendita
    function deleteDetail(int $id_sale, int $id_detail):int|false {
        // Per prima cosa dobbiamo controllare che il dettaglio sia effettivamente presente nella vendita
        $queryDetailSale = "SELECT id FROM " . SalesRepository::TABLE_SALES_DETAILS . " WHERE id_sale = :id_sale AND id = :id_detail";

        //Prepariamo lo statement, eseguiamo il bindValue e eseguiamo la query
        $stmtDetailSale = $this->pdo->prepare($queryDetailSale);
        $stmtDetailSale->bindValue(':id_sale', $id_sale, PDO::PARAM_INT);
        $stmtDetailSale->bindValue(':id_detail', $id_detail, PDO::PARAM_INT);
        $stmtDetailSale->execute();

        // Troviamo il record o no in base alla presenta del dettaglio in quella precisa vendita
        $checkDetailSale = $stmtDetailSale->fetch();

        // Se il dettaglio non esiste nella vendita selezionata allora ritorniamo subito
        if ($checkDetailSale === false) {
            return $checkDetailSale;
        } else {
            try {
                // Facciamo partire la transaction: permette di eseguire un gruppo di query SQL in una unica soluzione
                $this->pdo->beginTransaction();

                // Query SQL per eliminare un dettaglio specifico di una specifica vendita
                $queryDeleteDetail = "DELETE FROM " . SalesRepository::TABLE_SALES_DETAILS . " WHERE id_sale = :id_sale AND id = :id_detail";

                //Prepariamo lo statement, eseguiamo il bindValue e eseguiamo la query
                $stmtDeleteDetail = $this->pdo->prepare($queryDeleteDetail);
                $stmtDeleteDetail->bindValue(':id_sale', $id_sale, PDO::PARAM_INT);
                $stmtDeleteDetail->bindValue(':id_detail', $id_detail, PDO::PARAM_INT);
                $stmtDeleteDetail->execute();

                // Verifichiamo che il dettaglio sia stato effettivamente eliminato oppure no
                $checkDeleteDetail = $stmtDeleteDetail->rowCount();

                // Se il dettaglio è stato eliminato con successo allora controlliamo quanti dettagli sono presenti nella vendita
                if ($checkDeleteDetail === 1) {
                    /* Eseguiamo una query per verificare quanti dettagli sono rimasti nella vendita.
                    Se 0 dopo la l'eliminazione allora dobbiamo di conseguenza eliminare anche la vendita */
                    $queryCheckDetailsSale = "SELECT COUNT(*) FROM " . SalesRepository::TABLE_SALES_DETAILS . " WHERE id_sale = :id_sale";

                    //Prepariamo lo statement, eseguiamo il bindValue e eseguiamo la query
                    $stmtCheckDetailsSale = $this->pdo->prepare($queryCheckDetailsSale);
                    $stmtCheckDetailsSale->bindValue(':id_sale', $id_sale, PDO::PARAM_INT);
                    $stmtCheckDetailsSale->execute();

                    // Recuperiamo il valore restituito da count con fetchColumn e con (int) lo trasformiamo in intero
                    $remainingDetailsSale = (int) $stmtCheckDetailsSale->fetchColumn();

                    // Se la rimanenza nella vendita è zero allora dobbiamo eliminare quella specifica vendita
                    if ($remainingDetailsSale === 0) {
                        // Query per eliminare la vendita
                        $queryDeleteSale = "DELETE FROM " . SalesRepository::TABLE_SALES . " WHERE id = :id_sale";

                        //Prepariamo lo statemenr, bindValue e eseguiamo la query
                        $stmtDeleteSale = $this->pdo->prepare($queryDeleteSale);
                        $stmtDeleteSale->bindValue(':id_sale', $id_sale, PDO::PARAM_INT);
                        $stmtDeleteSale->execute();

                        // Chiudiamo ed eseguiamo la transaction con commit
                        $this->pdo->commit();
                        
                        // Ritorniamo 1 per la conferma dell'eliminazione anche della vendita
                        return 1;
                    }
                }
                // Chiudiamo ed eseguiamo la transaction con commit
                $this->pdo->commit();

                /* Ritorniamo 0 visto che è stata effettuata solo la cancellazione del dettaglio */
                return 0;
            } catch (Throwable $error) {
                // Se la transizione è ancora attiva la interrompiamo e mandiamo vari errori o eccezzioni al service che li gestirà
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                throw $error;
            }
        }
    }

    // Creiamo il metodo existsById() per verificare tramite query che l'id_sale esista nella tabella
    function existsById(int $id_sale): bool
    {
        // Creiamo la query che cerca il record giusto tramite l'id
        $query = "SELECT EXISTS (SELECT 1 FROM " . SalesRepository::TABLE_SALES . " WHERE id = :id)";

        // Prepariamo lo statement, effettuaimo il bindValue e eseguiamo lo statement
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':id', $id_sale, PDO::PARAM_INT);
        $stmt->execute();

        // fetchColumn() restituisce 1/0 in base alla presenza, con (bool) lo trasformiamo in true/false
        $booleanResult = (bool) $stmt->fetchColumn();
        return $booleanResult;
    }

}