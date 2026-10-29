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

    /* Metodo getAllSales restituisce tutte le vendite effettuate:
        1. Query SQL: la query utilizza gli alias per accorciare la nomenclatura delle tabella.
            Utilizziamo inner join per effettuare collegamenti relazionali tra diverse tabelle;
        2. Prepariamo lo statement e lo eseguiamo;
        3. Recuperiamo i dati come array associativo;
        4. Ritorniamo l'array con tutte le vendite. */
    function getAll():array {
        $query = "SELECT s.id, s.sale_date, p.name, sd.quantity 
            FROM " . SalesRepository::TABLE_SALES . " s 
            INNER JOIN sales_details sd ON s.id = sd.id_sale 
            INNER JOIN prestations p ON p.id = sd.id_prestation
            ORDER BY s.id ASC, sd.id ASC";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute();

        $allSales = $stmt->fetchAll();
        return $allSales;
    }

    /* Metodo getById() restituisce la vendita selezionata tramite l'id:
        1. Creiamo la Query SQL;
        2. Prepariamo lo statement, eseguiamo il bindValue e lo eseguiamo;
        3. Recuperiamo i dati con un array associativo: può essere che ci sia solo una riga per una singola vendita con una prestazione oppure più righe,
            quindi più prestazioni per la singola vendita. Nel caso che l'id non esista fetchAll() restituisce un array vuoto;
        4. Ritorniamo la vendita selezionata. */
    function getById(int $id_sale):array {
        $query = "SELECT s.id, s.sale_date, p.name, sd.quantity
        FROM " . SalesRepository::TABLE_SALES . " s 
        INNER JOIN " . SalesRepository::TABLE_SALES_DETAILS . " sd ON s.id = sd.id_sale
        INNER JOIN " . SalesRepository::TABLE_PRESTATIONS . " p ON p.id = sd.id_prestation
        WHERE s.id = :id_sale";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':id_sale', $id_sale, PDO::PARAM_INT);
        $stmt->execute();

        $sale = $stmt->fetchAll();
        return $sale;
    }

    /* Metodo create(): il metodo create è più complicato visto che inserisce dati in due tabelle differenti:
        1. La vendita viene creata quindi di conseguenza l'id e viene impostata in automatico la data corrente;
        2. Il file json che riceve ha semplicemente un array con le tipologie di prestazioni e le loro quantità;
        3. Utilizziamo il try/catch per gestire gli errori/eccezioni;
        4. Facciamo partire la transaction: permette di eseguire un gruppo di query SQL in una unica soluzione;
        5. Query SQL per inserire una nuova vendita e la data corrente;
        6. Prepariamo lo statement e lo eseguiamo;
        7. Una volta eseguito lo stmt recuperiamo l'ultimo id inserito all'interno della tabella;
        8. Query SQL per inserire i dettagli della vendita;
        9. Prepariamo lo stmt con la query;
        10. Eseguiamo un foreach, per ogni detail dell'array details in ingresso: 
            10.1. Estraiamo l'id_prestation e la quantity;
            10.2. Recuperiamo il time_saved della prestazione specifica dalla tabella prestations tramite una nuova chiamata al DB;
            10.3. Se l'id_prestation inserito non esiste allora rollback e creazione nuova eccezione
            10.4. Colleghiamo i parametri dinamici alla query;
            10.5. Eseguiamo lo stmt;
            10.6. Con fetchColumn estraiamo il valore dell'unica riga dell'unica colonna;
            10.7. Utilizziamo $time_saved_snapshot === false perchè lo 0 è falsy ma valido per le regole di dominio di time_saved.
                    Mandiamo un'eccezione che la prestazione abbinata a quell'id non esiste. InvalidArgumentException fa saltare 
                    direttamente al catch senza eseguire più nulla, così facendo si attiva il rollBack visto che la transaction è ancora attiva e la disattiva.
            10.8. Con bindValue() andiamo a collegare i placeholder con i valori in ingresso della funzione;
            10.9. Eseguiamo lo statement - query con execute;
        11. Fuori dal foreach chiudiamo la transaction con commit() ed esegue tutto insieme;
        12. Ritorniamo l'id dell'ultima vendita inserita;
        13. Nel catch se la transizione è ancora attiva la interrompiamo e mandiamo vari errori o eccezzioni al service che li gestirà*/
    function create(array $details):int {
        try {
            $this->pdo->beginTransaction();

            $querySale = "INSERT INTO " . SalesRepository::TABLE_SALES . " (sale_date) VALUES (CURRENT_DATE)";

            $stmt = $this->pdo->prepare($querySale);
            $stmt->execute();

            $newIdSale = (int) $this->pdo->lastInsertId();

            $querySaleDetails = "INSERT INTO " . SalesRepository::TABLE_SALES_DETAILS . " (id_sale, id_prestation, quantity, time_saved_snapshot)
                VALUES (:id_sale, :id_prestation, :quantity, :time_saved_snapshot)";

            $stmt = $this->pdo->prepare($querySaleDetails);

            foreach ($details as $detail){
                $id_prestation = $detail['id_prestation'];
                $quantity = $detail['quantity'];

                $queryTimeSaved = "SELECT time_saved FROM " . SalesRepository::TABLE_PRESTATIONS . " WHERE id = :id AND active = true";
                $stmtTimeSaved = $this->pdo->prepare($queryTimeSaved);
                $stmtTimeSaved->bindValue(':id', $id_prestation, PDO::PARAM_INT);
                $stmtTimeSaved->execute();
                $time_saved_snapshot = $stmtTimeSaved->fetchColumn();

                if ($time_saved_snapshot === false) {
                    throw new InvalidArgumentException("La prestazione " . $id_prestation . " non esiste", 404);
                }

                $stmt->bindValue(':id_sale', $newIdSale, PDO::PARAM_INT);
                $stmt->bindValue(':id_prestation', $id_prestation, PDO::PARAM_INT);
                $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
                $stmt->bindValue(':time_saved_snapshot', $time_saved_snapshot, PDO::PARAM_INT);
                $stmt->execute();
            }
            $this->pdo->commit();
            return $newIdSale;

        } catch ( Throwable $error) {
            if($this->pdo->inTransaction()){
                $this->pdo->rollBack();
            }
            throw $error;
        }
    } 

    /* Metodo delete(), elimina la vendita selezionata e di conseguenza, in cascata, anche i dettagli correlati:
        1. Query SQL per cancellare la vendita con l'id selezionato;
        2. Prepariamo lo statement, eseguiamo il bindValue e lo eseguiamo;
        3. Eseguiamo una conferma che effettivamente la vendita sia stata eseguita se $row 1 allora è stata eseguita,
            se 0 allora l'id è inesistente e non è stata eliminata nessuna riga;
        4. Ritorniamo il risultato. */
    function delete(int $id_sale):int {
        $query = "DELETE FROM " . SalesRepository::TABLE_SALES . " WHERE id = :id_sale";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':id_sale', $id_sale, PDO::PARAM_INT);
        $stmt->execute();

        $deletedRow = $stmt->rowCount();
        return $deletedRow;
    }

    /* Metodo addDetails(): aggiunge 1 o + dettagli ad una vendita esistente 
    La logica sarà simile a quella del metodo create():
        1. Apriamo il try/catch per la gestione degli errori;
        2. Effetuiamo una verifica che la vendita effettivamente esista tramite l'id;
        3. Prepariamo lo statement, bindValue ed eseguiamo lo statement;
        4. Il risultato di fetchAll ci dice se la vendita esiste 1, oppure non esiste 0;
        5. Se la vendita non esiste allora ritorniamo subito 0;
        6. Facciamo partire la transaction: permette di eseguire un gruppo di query SQL in una unica soluzione;
        7. Creiamo la query per inserire i dettagli nella vendita selezionata;
        8. Prepariamo lo statement;
        9 Eseguiamo il foreach, per ogni dettaglio presente nell'array dettagli:
            9.1. Estraiamo l'id_prestation e la quantity;
            9.2. Recuperiamo il time_saved della prestazione specifica dalla tabella prestations tramite una nuova chiamata al DB;
            9.3. Se l'id_prestation inserito non esiste allora rollback e creazione nuova eccezione
            9.4. Colleghiamo i parametri dinamici alla query;
            9.5. Eseguiamo lo stmt;
            9.6. Con fetchColumn estraiamo il valore dell'unica riga dell'unica colonna;
            9.7. Utilizziamo $time_saved_snapshot === false perchè lo 0 è falsy ma valido per le regole di dominio di time_saved.
                    Mandiamo un'eccezione che la prestazione abbinata a quell'id non esiste. InvalidArgumentException fa saltare 
                    direttamente al catch senza eseguire più nulla, così facendo si attiva il rollBack visto che la transaction è ancora attiva e la disattiva.
            9.8. Con bindValue() andiamo a collegare i placeholder con i valori in ingresso della funzione;
            9.9. Eseguiamo lo statement - query con execute;
        10. Fuori dal foreach chiudiamo la transaction con commit;
        11. Ritorniamo il valore 1 harcodato per confermare l'aggiornamento della vendita.
        12. Nel catch se la transizione è ancora attiva la interrompiamo e mandiamo vari errori o eccezzioni al service che li gestirà. */
    function addDetails(int $id_sale, array $details):int {
        try {
            // 
            $querySale = "SELECT id FROM " . SalesRepository::TABLE_SALES . " WHERE id = :id";
            $stmtSale = $this->pdo->prepare($querySale);
            $stmtSale->bindValue(':id', $id_sale, PDO::PARAM_INT);
            $stmtSale-> execute();
            $checkSale = count($stmtSale->fetchAll());

            if ($checkSale === 0) {
                return $checkSale;
            } else {
                $this->pdo->beginTransaction();

                $querySaleDetails = "INSERT INTO " . SalesRepository::TABLE_SALES_DETAILS . " (id_sale, id_prestation, quantity, time_saved_snapshot)
                    VALUES (:id_sale, :id_prestation, :quantity, :time_saved_snapshot)";
                $stmt = $this->pdo->prepare($querySaleDetails);

                foreach ($details as $detail){
                    $id_prestation = $detail['id_prestation'];
                    $quantity = $detail['quantity'];

                    $queryTimeSaved = "SELECT time_saved FROM " . SalesRepository::TABLE_PRESTATIONS . " WHERE id = :id AND active = true";
                    $stmtTimeSaved = $this->pdo->prepare($queryTimeSaved);
                    $stmtTimeSaved->bindValue(':id', $id_prestation, PDO::PARAM_INT);
                    $stmtTimeSaved->execute();
                    $time_saved_snapshot = $stmtTimeSaved->fetchColumn();

                    if ($time_saved_snapshot === false) {
                        throw new InvalidArgumentException("La prestazione " . $id_prestation . " non esiste", 404);
                    }
                    
                    $stmt->bindValue(':id_sale', $id_sale, PDO::PARAM_INT);
                    $stmt->bindValue(':id_prestation', $id_prestation, PDO::PARAM_INT);
                    $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
                    $stmt->bindValue(':time_saved_snapshot', $time_saved_snapshot, PDO::PARAM_INT);

                    $stmt->execute();
                }
                $this->pdo->commit();
                return 1;
            }

        } catch ( Throwable $error) {
            if($this->pdo->inTransaction()){
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    /* Metodo deleteDetail(), eliminare un dettaglio specifico di una specifica vendita:
        1. Creiamo una query SQL per controllare che effettivaemnte la vendita esista;
        2. Prepariamo lo statement, effettuaimo il bindValue e eseguiamo lo statement;
        3. fetchColumn() restituisce 1/0 in base alla presenza, con (bool) lo trasformiamo in true/false;
        4. Se il valore è false allora ritorniamo direttamente false;
        5. Altrimenti creiamo una query per controllare che effettivamente il dettaglio esista all'interno della vendita selezionata;
        6. Prepariamo lo statement, eseguiamo il bindValue e eseguiamo la query;
        7. Salviamo il valore effettivo in base a se il dettaglio è stato trovato o no;
        8. Se il dettaglio non esiste all'interno della vendita allora ritorniamo 0;
        9. Altrimenti apriamo un try/catch per la gestione degli errori/eccezioni;
        10. Facciamo partire la transaction: permette di eseguire un gruppo di query SQL in una unica soluzione;
        11. Creiamo la Query SQL per eliminare un dettaglio specifico di una specifica vendita;
        12. Prepariamo lo statement, eseguiamo il bindValue e eseguiamo la query;
        13. Salviamo il risultato della verifica che il dettaglio sia stato effettivamente eliminato oppure no;
        14. Se il dettaglio è stato eliminato con successo allora controlliamo quanti dettagli sono presenti nella vendita;
        15. Creiamo una query per verificare quanti dettagli sono rimasti nella vendita. Se 0 dopo la l'eliminazione,
            allora dobbiamo di conseguenza eliminare anche la vendita;
        16. Prepariamo lo statement, eseguiamo il bindValue e eseguiamo la query;
        17. Recuperiamo il valore restituito da count con fetchColumn e con (int) lo trasformiamo in intero;
        18. Se la rimanenza nella vendita è zero allora dobbiamo eliminare quella specifica vendita;
        19. Creiamo la Query SQL per eliminare la vendita selezionata;
        20. Prepariamo lo statemenr, bindValue e eseguiamo la query;
        21. Chiudiamo ed eseguiamo la transaction con commit;
        22. Ritorniamo 2 per la conferma dell'eliminazione anche della vendita;
        23. Se invece sono ancora presenti degli altri dettagli allora chiudiamo la transaction con commit;
        24. Ritorniamo 1 visto che è stata effettuata solo la cancellazione del dettaglio;
        25. Nel catch se la transizione è ancora attiva la interrompiamo e mandiamo vari errori o eccezzioni al service che li gestirà. */
    function deleteDetail(int $id_sale, int $id_detail):int|false {
        $query = "SELECT EXISTS (SELECT 1 FROM " . SalesRepository::TABLE_SALES . " WHERE id = :id)";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':id', $id_sale, PDO::PARAM_INT);
        $stmt->execute();

        $booleanResultSale = (bool) $stmt->fetchColumn();

        if ($booleanResultSale === false) {
            return false;
        } else {
            $queryDetailSale = "SELECT id FROM " . SalesRepository::TABLE_SALES_DETAILS . " WHERE id_sale = :id_sale AND id = :id_detail";

            $stmtDetailSale = $this->pdo->prepare($queryDetailSale);
            $stmtDetailSale->bindValue(':id_sale', $id_sale, PDO::PARAM_INT);
            $stmtDetailSale->bindValue(':id_detail', $id_detail, PDO::PARAM_INT);
            $stmtDetailSale->execute();

            $checkDetailSale = $stmtDetailSale->fetch();
        }
        if ($checkDetailSale === false) {
            return 0;
        } else {
            try {
                $this->pdo->beginTransaction();

                $queryDeleteDetail = "DELETE FROM " . SalesRepository::TABLE_SALES_DETAILS . " WHERE id_sale = :id_sale AND id = :id_detail";

                $stmtDeleteDetail = $this->pdo->prepare($queryDeleteDetail);
                $stmtDeleteDetail->bindValue(':id_sale', $id_sale, PDO::PARAM_INT);
                $stmtDeleteDetail->bindValue(':id_detail', $id_detail, PDO::PARAM_INT);
                $stmtDeleteDetail->execute();

                $checkDeleteDetail = $stmtDeleteDetail->rowCount();

                if ($checkDeleteDetail === 1) {
                    $queryCheckDetailsSale = "SELECT COUNT(*) FROM " . SalesRepository::TABLE_SALES_DETAILS . " WHERE id_sale = :id_sale";

                    $stmtCheckDetailsSale = $this->pdo->prepare($queryCheckDetailsSale);
                    $stmtCheckDetailsSale->bindValue(':id_sale', $id_sale, PDO::PARAM_INT);
                    $stmtCheckDetailsSale->execute();

                    $remainingDetailsSale = (int) $stmtCheckDetailsSale->fetchColumn();

                    if ($remainingDetailsSale === 0) {
                        $queryDeleteSale = "DELETE FROM " . SalesRepository::TABLE_SALES . " WHERE id = :id_sale";

                        $stmtDeleteSale = $this->pdo->prepare($queryDeleteSale);
                        $stmtDeleteSale->bindValue(':id_sale', $id_sale, PDO::PARAM_INT);
                        $stmtDeleteSale->execute();

                        $this->pdo->commit();
                        return 2;
                    }
                }
                $this->pdo->commit();
                return 1;
            } catch (Throwable $error) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                throw $error;
            }
        }
    }

    /* Creiamo il metodo existsById(), per verificare tramite query che l'id_sale esista nella tabella:
        1. Creiamo la query che cerca il record giusto tramite l'id;
        2. Prepariamo lo statement, effettuaimo il bindValue e eseguiamo lo statement;
        3. Salviamo il risutlato di fetchColumn() che restituisce 1/0 in base alla presenza, con (bool) lo trasformiamo in true/false;
        4. Ritorniamo il risultato. */
    function existsById(int $id_sale): bool {
        $query = "SELECT EXISTS (SELECT 1 FROM " . SalesRepository::TABLE_SALES . " WHERE id = :id)";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':id', $id_sale, PDO::PARAM_INT);
        $stmt->execute();

        $booleanResult = (bool) $stmt->fetchColumn();
        return $booleanResult;
    }

    /* Creiamo il metodo getTotalTimeSaved() per il calcolo totale del tempo risparmiato dalle vendite delle prestazioni:
        1. In ingresso abbiamo i tre parametri opzionali;
        2. Query SQL: 
            2.1. Utilizziamo SUM per la somma della moltiplicazione tra time_saved_snapshot e quantity,
                SUM se il valore è 0 da null.
            2.2. Utilizziamo COALESCE per trasformare il primo valore in 0 nel caso fosse null;
        3. Creiamo un array vuoto per i parametri $params;
        4. Creiamo un array vuoto per le condizioni possibili della query $conditions;
        4. Se il parametro id_prestation è presente allora salva il pezzo di query in $conditions e il parametro nell'array $params;
        5. Se è presente una data o entrambe viene aggiunto l'inner join alla query per collegare la data di vendita;
        6. Se la data di inizio è presente aggiungiamo a $condition il pezzo di query  e il parametro in $params;
        7. Se la data di fine è presente aggiungiamo a $condition il pezzo di query  e il parametro in $params;
        8. Se l'array conditions non è vuoto aggiungiamo il pezzo di query con implode per mettere un separatore AND alle varie condizioni;
        9. Prepariamo lo statement con la query dinamica;
        10. Eseguiamo la query passando in ingresso i parametri;
        11. Il valore restituito lo trasformiamo in intero (int) e lo salviamo in $totalTimeSaved;
        12. Restituiamo il valore $totalTimeSaved. */
    function getTotalTimeSaved(?int $id_prestation, ?string $start_date, ?string $end_date):int {
         
        $query = "SELECT COALESCE(SUM(sd.time_saved_snapshot * sd.quantity), 0) FROM " . SalesRepository::TABLE_SALES_DETAILS . " sd";
        $params = [];
        $conditions = [];

        if ($id_prestation !== null) {
            $conditions[] = "sd.id_prestation = :id_prestation";
            $params[':id_prestation'] = (int) $id_prestation;
        }
        if ($start_date !== null || $end_date !== null) {
            $query .= " INNER JOIN " . SalesRepository::TABLE_SALES . " s ON s.id = sd.id_sale";
        }
        if ($start_date !== null) {
            $conditions[] = "s.sale_date >= :start_date";
            $params[':start_date'] = $start_date;
        }
        if ($end_date !== null) {
            $conditions[] = "s.sale_date <= :end_date";
            $params[':end_date'] = $end_date;
        }
        if ($conditions !== []) {
            $query .= " WHERE " . implode(' AND ', $conditions);
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);

        $totalTimeSaved = (int) $stmt->fetchColumn();

        return $totalTimeSaved;
    }

    /* Creiamo il metodo existsByIdPrestation(), per verificare tramite query che l'id_prestation esista nella tabella:
        1. Creiamo la query che cerca il record giusto tramite l'id;
        2. Prepariamo lo statement, effettuaimo il bindValue e eseguiamo lo statement;
        3. Salviamo il risutlato di fetchColumn() che restituisce 1/0 in base alla presenza, con (bool) lo trasformiamo in true/false;
        4. Ritorniamo il risultato. */
    function existsByIdPrestation(int $id_prestation): bool {
        $query = "SELECT EXISTS (SELECT 1 FROM " . SalesRepository::TABLE_PRESTATIONS . " WHERE id = :id)";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':id', $id_prestation, PDO::PARAM_INT);
        $stmt->execute();

        $booleanResult = (bool) $stmt->fetchColumn();
        return $booleanResult;
    }

}