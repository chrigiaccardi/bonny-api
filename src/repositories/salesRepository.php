<?php
/* Il file salesRepository conterrà i metodi con le query SQL da utilizzare 
per interrogare il DB per le vendite */

namespace Bonny\repositories;

use InvalidArgumentException;
use PDO;
use Throwable;

// Creiamo la classe salesRepository
class SalesRepository {
    // Creiamo una costante per il nome della tabella così da riutilizzarla
    private const TABLE_SALES = "sales";
    private const TABLE_SALES_DETAILS = "sales_details";
    private const TABLE_PRESTATIONS = "prestations";

    /* Utilizziamo il constructor property promotion per abbreviare il codice e automaticamente
    creare la variabile con l'oggetto associato*/
    public function __construct(readonly PDO $pdo) {}

    // Metodo getAllSales restituisce tutte le vendite effettuate:
    function getAll():array {
        /* 1. Query SQL: la query utilizza gli alias per accorciare la nomenclatura delle tabella.
            Utilizziamo inner join per effettuare collegamenti relazionali tra diverse tabelle; */
        $query = "SELECT s.id, s.sale_date, p.name, sd.quantity 
            FROM " . SalesRepository::TABLE_SALES . " s 
            INNER JOIN sales_details sd ON s.id = sd.id_sale 
            INNER JOIN prestations p ON p.id = sd.id_prestation
            ORDER BY s.id ASC, sd.id ASC";

        // 2. Prepariamo lo statement e lo eseguiamo;
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();

        // 3. Recuperiamo i dati come array associativo;
        $allSales = $stmt->fetchAll();

        // 4. Ritorniamo l 'array con tutte le vendite.
        return $allSales;
    }

    // Metodo getById() restituisce la vendita selezionata tramite l'id:
    function getById(int $id_sale):array {
        // 1. Creiamo la Query SQL;
        $query = "SELECT s.id, s.sale_date, p.name, sd.quantity
        FROM " . SalesRepository::TABLE_SALES . " s 
        INNER JOIN " . SalesRepository::TABLE_SALES_DETAILS . " sd ON s.id = sd.id_sale
        INNER JOIN " . SalesRepository::TABLE_PRESTATIONS . " p ON p.id = sd.id_prestation
        WHERE s.id = :id_sale";

        // 2. Prepariamo lo statement, eseguiamo il bindValue e lo eseguiamo;
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':id_sale', $id_sale, PDO::PARAM_INT);
        $stmt->execute();

        /* 3. Recuperiamo i dati con un array associativo: può essere che ci sia solo una riga per una singola vendita con una prestazione oppure più righe,
            quindi più prestazioni per la singola vendita. Nel caso che l'id non esista fetchAll() restituisce un array vuoto; */
        $sale = $stmt->fetchAll();

        // 4. Ritorniamo la vendita selezionata.
        return $sale;
    }

    // Metodo create(): il metodo create è più complicato visto che inserisce dati in due tabelle differenti:
    function create(array $details):int {
        try {
            // 1. Inizio Transazione
            $this->pdo->beginTransaction();

            // 2. La vendita viene creata quindi di conseguenza l'id e viene impostata in automatico la data corrente;
            $querySale = "INSERT INTO " . SalesRepository::TABLE_SALES . " (sale_date) VALUES (CURRENT_DATE)";

            // 3. Prepariamo lo stmt con la query e la eseguiamo
            $stmt = $this->pdo->prepare($querySale);
            $stmt->execute();

            // 4. Recuperiamo l'ID della nuova vendita creata
            $newIdSale = (int) $this->pdo->lastInsertId();
            
            // 5. Creiamo due array: $placeholders e $params dove verranno inseriti dinamicamente i $placeholders e i parametri.
            $placeholders = [];
            $params = [];

            /* 6. Per ogni dettaglio all'interno dei dettagli recuperiamo l'index e andiamo a creare i placeholders,
             inserendoli nell'array e abbinandoli con l'ID della prestazione */
            foreach ($details as $index => $detail) {
                $placeholder = ':id' . $index;
                $placeholders[] = $placeholder;
                $params[$placeholder] = $detail['id_prestation'];
            }

            // 7. Utilizziamo implode per trasformare l'array in una stringa con la virgola come separatore dei vari elementi.
            $placeholdersString = implode(', ',$placeholders);

            // 8. Effettuiamo la query per trovare gli ID selezionati e i suoi time_saved
            $queryTimeSaved = "SELECT id, time_saved FROM " . SalesRepository::TABLE_PRESTATIONS . " WHERE active = true AND id IN ($placeholdersString)";
            $stmtTimeSaved = $this->pdo->prepare($queryTimeSaved);
            $stmtTimeSaved->execute($params);
            
            // 9. La query ci ritorna un array associativo dove gli ID sono associati al tempo risparmiato
            $detailsPrestations = $stmtTimeSaved->fetchAll();

            /* 10. Creiamo un array vuoto dove andiamo ad inserire le associazioni di ogni valore ID ad ogni suo valore del tempo risparmiato.
                Questo ci permette di togliere la chiave dall'array di ritorno dalla query.
                Questa procedura viene fatta per semplificare il richiamo dei valori*/
            $timeSavedById = [];
            foreach($detailsPrestations as $detailPrestation) {
                $timeSavedById[$detailPrestation['id']] = $detailPrestation['time_saved'];
            }

            /* 11. Per ogni dettaglio all'interno di dettagli, recupariamo l'id_prestation e con isset,
                verifichiamo che la variabile esista e abbia un valore diverso da null, se false allora
                manda eccezione con statusCode 404. */
            foreach($details as $detail){
                $id_prestation = $detail['id_prestation'];
                if (isset($timeSavedById[$id_prestation]) === false) {
                    throw new InvalidArgumentException("La prestazione " . $id_prestation . " inesistente", 404);
                }
            }

            // 12. Creiamo la query per inserire il dettaglio all'interno della tabella sales_details e prepariamo lo stmt
            $querySaleDetails = "INSERT INTO " . SalesRepository::TABLE_SALES_DETAILS . " (id_sale, id_prestation, quantity, time_saved_snapshot)
                VALUES (:id_sale, :id_prestation, :quantity, :time_saved_snapshot)";
            $stmt = $this->pdo->prepare($querySaleDetails);

            /* 13. Per ogni dettaglio all'interno di dettagli recuperiamo l'id_prestation e la quantità;
                recuperiamo il time_saved dall'array creato in precedenza associato all'id_prestation;
                effettuiamo il bindValue per la sostituzione dei placeholders con i valori;
                eseguiamo lo stmt */
            foreach ($details as $detail){
                $id_prestation = $detail['id_prestation'];
                $quantity = $detail['quantity'];
                $time_saved_snapshot = $timeSavedById[$id_prestation];

                $stmt->bindValue(':id_sale', $newIdSale, PDO::PARAM_INT);
                $stmt->bindValue(':id_prestation', $id_prestation, PDO::PARAM_INT);
                $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
                $stmt->bindValue(':time_saved_snapshot', $time_saved_snapshot, PDO::PARAM_INT);
                $stmt->execute();
            }
            // 14. Eseguiamo il commit per terminare la transazione e ritorniamo il valore ID della vendita appena creata.
            $this->pdo->commit();
            return $newIdSale;
        
        // 15. Nel catch gestiamo gli errori catturati all'interno del try, se la transazione è ancora attiva la terminiamo.
        } catch ( Throwable $error) {
            if($this->pdo->inTransaction()){
                $this->pdo->rollBack();
            }
            throw $error;
        }
    } 

    // Metodo delete(), elimina la vendita selezionata e di conseguenza, in cascata, anche i dettagli correlati:
    function delete(int $id_sale):int {
        // 1. Query SQL per cancellare la vendita con l'id selezionato;
        $query = "DELETE FROM " . SalesRepository::TABLE_SALES . " WHERE id = :id_sale";

        // 2. Prepariamo lo statement, eseguiamo il bindValue e lo eseguiamo;
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':id_sale', $id_sale, PDO::PARAM_INT);
        $stmt->execute();

        /* 3. Eseguiamo una conferma che effettivamente la vendita sia stata eseguita se $row 1 allora è stata eseguita,
            se 0 allora l'id è inesistente e non è stata eliminata nessuna riga; */
        $deletedRow = $stmt->rowCount();

        // 4. Ritorniamo il risultato.
        return $deletedRow;
    }

    /* Metodo addDetails(): aggiunge 1 o + dettagli ad una vendita esistente 
    La logica sarà simile a quella del metodo create(): */
    function addDetails(int $id_sale, array $details):int {
        try {
            // 1. Effetuiamo una verifica che la vendita effettivamente esista tramite l'id;
            $querySale = "SELECT id FROM " . SalesRepository::TABLE_SALES . " WHERE id = :id";

            // 2. Prepariamo lo statement, bindValue ed eseguiamo lo statement;
            $stmtSale = $this->pdo->prepare($querySale);
            $stmtSale->bindValue(':id', $id_sale, PDO::PARAM_INT);
            $stmtSale-> execute();

            // 3. Il risultato di fetchColumn() ci dice se la vendita esiste true, oppure non esiste false;
            $checkSale = $stmtSale->fetchColumn();

            // 4. Se false allora ritorna 0 direttamente;
            if ($checkSale === false) {
                return 0;
            } else {
                // 5. Se la vendita esiste iniziamo la transazione;
                $this->pdo->beginTransaction();

                // 6. Creiamo due array: $placeholders e $params dove verranno inseriti dinamicamente i $placeholders e i parametri.
                $placeholders = []; 
                $params = [];

                /* 7. Per ogni dettaglio all'interno dei dettagli recuperiamo l'index e andiamo a creare i placeholders,
                    inserendoli nell'array e abbinandoli con l'ID della prestazione */
                foreach ($details as $index => $detail) {
                    $placeholder = ':id' . $index;
                    $placeholders[] = $placeholder;
                    $params[$placeholder] = $detail['id_prestation'];
                }

                // 8. Utilizziamo implode per trasformare l'array in una stringa con la virgola come separatore dei vari elementi.
                $placeholdersString = implode(', ', $placeholders);

                // 9. Effettuiamo la query per trovare gli ID selezionati e i suoi time_saved
                $queryTimeSaved = "SELECT id, time_saved FROM " . SalesRepository::TABLE_PRESTATIONS . " WHERE active = true AND id IN ($placeholdersString)";
                $stmtTimeSaved = $this->pdo->prepare($queryTimeSaved);
                $stmtTimeSaved->execute($params);

                // 10. La query ci ritorna un array associativo dove gli ID sono associati al tempo risparmiato
                $detailsPrestations = $stmtTimeSaved->fetchAll();

                /* 11. Creiamo un array vuoto dove andiamo ad inserire le associazioni di ogni valore ID ad ogni suo valore del tempo risparmiato.
                Questo ci permette di togliere la chiave dall'array di ritorno dalla query.
                Questa procedura viene fatta per semplificare il richiamo dei valori*/
                $timeSavedById = [];
                foreach ($detailsPrestations as $detailPrestation) {
                    $timeSavedById[$detailPrestation['id']] = $detailPrestation['time_saved'];
                }

                /* 12. Per ogni dettaglio all'interno di dettagli, recupariamo l'id_prestation e con isset,
                verifichiamo che la variabile esista e abbia un valore diverso da null, se false allora
                manda eccezione con statusCode 404. */
                foreach ($details as $detail) {
                    $id_prestation = $detail['id_prestation'];
                    if (isset($timeSavedById[$id_prestation]) === false) {
                        throw new InvalidArgumentException("La prestazione " . $id_prestation . " inesistente", 404);
                    }
                }

                // 13. Creiamo la query per inserire il dettaglio all'interno della tabella sales_details e prepariamo lo stmt
                $querySaleDetails = "INSERT INTO " . SalesRepository::TABLE_SALES_DETAILS . " (id_sale, id_prestation, quantity, time_saved_snapshot)
                VALUES (:id_sale, :id_prestation, :quantity, :time_saved_snapshot)";
                $stmt = $this->pdo->prepare($querySaleDetails);

                /* 14. Per ogni dettaglio all'interno di dettagli recuperiamo l'id_prestation e la quantità;
                recuperiamo il time_saved dall'array creato in precedenza associato all'id_prestation;
                effettuiamo il bindValue per la sostituzione dei placeholders con i valori;
                eseguiamo lo stmt */
                foreach ($details as $detail) {
                    $id_prestation = $detail['id_prestation'];
                    $quantity = $detail['quantity'];
                    $time_saved_snapshot = $timeSavedById[$id_prestation];


                    $stmt->bindValue(':id_sale', $id_sale, PDO::PARAM_INT);
                    $stmt->bindValue(':id_prestation', $id_prestation, PDO::PARAM_INT);
                    $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
                    $stmt->bindValue(':time_saved_snapshot', $time_saved_snapshot, PDO::PARAM_INT);
                    $stmt->execute();
                }
                // 15. Eseguiamo il commit per terminare la transazione e ritorniamo 1 per il successo dell'operazione.
                $this->pdo->commit();
                return 1;
            }

        // 16. Nel catch gestiamo gli errori catturati all'interno del try, se la transazione è ancora attiva la terminiamo.
        } catch ( Throwable $error) {
            if($this->pdo->inTransaction()){
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    // Metodo deleteDetail(), eliminare un dettaglio specifico di una specifica vendita:      
    function deleteDetail(int $id_sale, int $id_detail):int|false {
        // 1. Creiamo una query SQL per controllare che effettivaemnte la vendita esista;
        $query = "SELECT EXISTS (SELECT 1 FROM " . SalesRepository::TABLE_SALES . " WHERE id = :id)";

        // 2. Prepariamo lo statement, effettuaimo il bindValue e eseguiamo lo statement;
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':id', $id_sale, PDO::PARAM_INT);
        $stmt->execute();

        // 3. fetchColumn() restituisce 1/0 in base alla presenza, con (bool) lo trasformiamo in true/false;
        $booleanResultSale = (bool) $stmt->fetchColumn();

        // 4. Se il valore è false allora ritorniamo direttamente false;
        if ($booleanResultSale === false) {
            return false;
        } else {
            // 5. Altrimenti creiamo una query per controllare che effettivamente il dettaglio esista all'interno della vendita selezionata;
            $queryDetailSale = "SELECT id FROM " . SalesRepository::TABLE_SALES_DETAILS . " WHERE id_sale = :id_sale AND id = :id_detail";

            // 6. Prepariamo lo statement, eseguiamo il bindValue e eseguiamo la query;
            $stmtDetailSale = $this->pdo->prepare($queryDetailSale);
            $stmtDetailSale->bindValue(':id_sale', $id_sale, PDO::PARAM_INT);
            $stmtDetailSale->bindValue(':id_detail', $id_detail, PDO::PARAM_INT);
            $stmtDetailSale->execute();

            // 7. Salviamo il valore effettivo in base a se il dettaglio è stato trovato o no;
            $checkDetailSale = $stmtDetailSale->fetch();
        }
        // 8. Se il dettaglio non esiste all'interno della vendita allora ritorniamo 0;
        if ($checkDetailSale === false) {
            return 0;
        } else {
            // 9. Altrimenti apriamo un try/catch per la gestione degli errori/eccezioni;
            try {
                //10. Facciamo partire la transazione: permette di eseguire un gruppo di query SQL in una unica soluzione;
                $this->pdo->beginTransaction();

                // 11. Creiamo la Query SQL per eliminare un dettaglio specifico di una specifica vendita;
                $queryDeleteDetail = "DELETE FROM " . SalesRepository::TABLE_SALES_DETAILS . " WHERE id_sale = :id_sale AND id = :id_detail";

                // 12. Prepariamo lo statement, eseguiamo il bindValue e eseguiamo la query;
                $stmtDeleteDetail = $this->pdo->prepare($queryDeleteDetail);
                $stmtDeleteDetail->bindValue(':id_sale', $id_sale, PDO::PARAM_INT);
                $stmtDeleteDetail->bindValue(':id_detail', $id_detail, PDO::PARAM_INT);
                $stmtDeleteDetail->execute();

                // 13. Salviamo il risultato della verifica che il dettaglio sia stato effettivamente eliminato oppure no;
                $checkDeleteDetail = $stmtDeleteDetail->rowCount();

                // 14. Se il dettaglio è stato eliminato con successo allora controlliamo quanti dettagli sono presenti nella vendita;
                if ($checkDeleteDetail === 1) {
                    /* 15. Creiamo una query per verificare quanti dettagli sono rimasti nella vendita. Se 0 dopo la l'eliminazione,
                        allora dobbiamo di conseguenza eliminare anche la vendita; */
                    $queryCheckDetailsSale = "SELECT COUNT(*) FROM " . SalesRepository::TABLE_SALES_DETAILS . " WHERE id_sale = :id_sale";

                    // 16. Prepariamo lo statement, eseguiamo il bindValue e eseguiamo la query;
                    $stmtCheckDetailsSale = $this->pdo->prepare($queryCheckDetailsSale);
                    $stmtCheckDetailsSale->bindValue(':id_sale', $id_sale, PDO::PARAM_INT);
                    $stmtCheckDetailsSale->execute();

                    // 17. Recuperiamo il valore restituito da count con fetchColumn e con (int) lo trasformiamo in intero;
                    $remainingDetailsSale = (int) $stmtCheckDetailsSale->fetchColumn();

                    // 18. Se la rimanenza nella vendita è zero allora dobbiamo eliminare quella specifica vendita;
                    if ($remainingDetailsSale === 0) {
                        // 19. Creiamo la Query SQL per eliminare la vendita selezionata;
                        $queryDeleteSale = "DELETE FROM " . SalesRepository::TABLE_SALES . " WHERE id = :id_sale";

                        // 20. Prepariamo lo statemenr, bindValue e eseguiamo la query;
                        $stmtDeleteSale = $this->pdo->prepare($queryDeleteSale);
                        $stmtDeleteSale->bindValue(':id_sale', $id_sale, PDO::PARAM_INT);
                        $stmtDeleteSale->execute();

                        // 21. Chiudiamo ed eseguiamo la transazione con commit e ritorniamo 2 per conferma dell'eliminazione della vendita;
                        $this->pdo->commit();
                        return 2;
                    }
                }

                // 22. Chiudiamo ed eseguiamo la transazione con commit e ritorniamo 1 per conferma dell'eliminazione di un dettaglio;
                $this->pdo->commit();
                return 1;

            // 23. Nel catch se la transizione è ancora attiva la interrompiamo e mandiamo vari errori o eccezzioni al service che li gestirà. */  
            } catch (Throwable $error) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                throw $error;
            }
        }
    }

    // Creiamo il metodo existsById(), per verificare tramite query che l'id_sale esista nella tabella:
    function existsById(int $id_sale): bool {
        //  1. Creiamo la query che cerca il record giusto tramite l'id;
        $query = "SELECT EXISTS (SELECT 1 FROM " . SalesRepository::TABLE_SALES . " WHERE id = :id)";

        // 2. Prepariamo lo statement, effettuaimo il bindValue e eseguiamo lo statement;
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':id', $id_sale, PDO::PARAM_INT);
        $stmt->execute();

        // 3. Salviamo il risultato di fetchColumn() che restituisce 1/0 in base alla presenza, con (bool) lo trasformiamo in true/false;
        $booleanResult = (bool) $stmt->fetchColumn();
        // 4. Ritorniamo il risultato.
        return $booleanResult;
    }

    // Creiamo il metodo getTotalTimeSaved() per il calcolo totale del tempo risparmiato dalle vendite delle prestazioni:
    function getTotalTimeSaved(?int $id_prestation, ?string $start_date, ?string $end_date):int {
        // 1. In ingresso abbiamo i tre parametri opzionali;

        /* 2. Query SQL: 
            2.1. Utilizziamo SUM per la somma della moltiplicazione tra time_saved_snapshot e quantity,
                SUM se il valore è 0 da null.
            2.2. Utilizziamo COALESCE per trasformare il primo valore in 0 nel caso fosse null; */
        $query = "SELECT COALESCE(SUM(sd.time_saved_snapshot * sd.quantity), 0) FROM " . SalesRepository::TABLE_SALES_DETAILS . " sd";

        // 3. Creiamo un array vuoto per i parametri $params;
        // 4. Creiamo un array vuoto per le condizioni possibili della query $conditions;
        $params = [];
        $conditions = [];

        // 5. Se il parametro id_prestation è presente allora salva il pezzo di query in $conditions e il parametro nell'array $params;
        if ($id_prestation !== null) {
            $conditions[] = "sd.id_prestation = :id_prestation";
            $params[':id_prestation'] = (int) $id_prestation;
        }

        // 6. Se è presente una data o entrambe viene aggiunto l'inner join alla query per collegare la data di vendita;
        if ($start_date !== null || $end_date !== null) {
            $query .= " INNER JOIN " . SalesRepository::TABLE_SALES . " s ON s.id = sd.id_sale";
        }

        // 7. Se la data di inizio è presente aggiungiamo a $condition il pezzo di query  e il parametro in $params;
        if ($start_date !== null) {
            $conditions[] = "s.sale_date >= :start_date";
            $params[':start_date'] = $start_date;
        }

        // 8. Se la data di fine è presente aggiungiamo a $condition il pezzo di query  e il parametro in $params;
        if ($end_date !== null) {
            $conditions[] = "s.sale_date <= :end_date";
            $params[':end_date'] = $end_date;
        }

        // 9. Se l'array conditions non è vuoto aggiungiamo il pezzo di query con implode per mettere un separatore AND alle varie condizioni;
        if ($conditions !== []) {
            $query .= " WHERE " . implode(' AND ', $conditions);
        }

        // 10. Prepariamo lo statement con la query dinamica;
        // 11. Eseguiamo la query passando in ingresso i parametri;
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);

        // 12. Il valore restituito lo trasformiamo in intero (int) e lo salviamo in $totalTimeSaved;
        $totalTimeSaved = (int) $stmt->fetchColumn();

        // 13. Restituiamo il valore $totalTimeSaved.
        return $totalTimeSaved;
    }

    // Creiamo il metodo existsByIdPrestation(), per verificare tramite query che l'id_prestation esista nella tabella:
    function existsByIdPrestation(int $id_prestation): bool {
        // 1. Creiamo la query che cerca il record giusto tramite l'id;
        $query = "SELECT EXISTS (SELECT 1 FROM " . SalesRepository::TABLE_PRESTATIONS . " WHERE id = :id)";

        // 2. Prepariamo lo statement, effettuaimo il bindValue e eseguiamo lo statement;
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':id', $id_prestation, PDO::PARAM_INT);
        $stmt->execute();

        // 3. Salviamo il risutlato di fetchColumn() che restituisce 1/0 in base alla presenza, con (bool) lo trasformiamo in true/false;
        $booleanResult = (bool) $stmt->fetchColumn();

        // Ritorniamo il risultato.
        return $booleanResult;
    }

}