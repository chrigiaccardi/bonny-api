<?php
/* Il salesController deve: leggere la Request HTTP; capire quale operazione è richiesta;
estrarre id, body JSON, ecc.; chiamare il Service; trasformare il risultato in una Response HTTP JSON;
gestire le eccezioni applicative e assegnare lo status HTTP appropriato. */

// Utilizziamo declare() per rendere il file in modalità strict e generare TypeError
declare(strict_types=1);

// Inseriamo un namespace per non avere conflitti in futuro e identificare la classe all'interno dell'architettura
namespace Bonny\controllers;

use Bonny\services\SalesService;
use Throwable;

class SalesController{
    // Dichiariamo la proprieta $salesService per poterla avere all'interno di questo file
    private SalesService $salesService;

    // Utilizziamo il costruttore per poter salvare e utilizzare la classe SalesService
    public function __construct(SalesService $salesService){
        $this->salesService = $salesService;
    }

    // Creazione di funzioni private per riutilizzarle dentro i metodo (DRY):

    /* Funzione validateArray():
        1. Validiamo che l'array in ingresso sia di formato lista;
        2. Con un foreach verifichiamo che per ogni dettaglio dentro dettagli:
            2.1. Controlliamo che il singolo dettaglio sia un array;
            2.2. Controlliamo che i campi esistano;
            2.3. Controlliamo che i campi siano di tipizzazione corretta; */
    private function validateArray(array $details){
        if (!array_is_list($details)) {
                sendJson(['error' => 'La lista di dettagli da inserire nella vendita ha un formato errato'], 422);
            }

            foreach ($details as $detail) {
                if (!is_array($detail)) {
                    sendJson(['error' => 'Dettaglio inserito con il formato errato'], 422);
                }
                if (!array_key_exists('id_prestation', $detail)) {
                    sendJson(['error' => 'Il campo Prestazione è obbligatorio'], 422);
                }

                if (!array_key_exists('quantity', $detail)) {
                    sendJson(['error' => 'Il campo Quantità è obbligatorio'], 422);
                }

                if (!is_int($detail['id_prestation'])) {
                    sendJson(['error' => "Impossibile aggiungere la prestazione selezionata. Verifica la selezione e riprova."], 422);
                }

                if (!is_int($detail['quantity'])) {
                    sendJson(['error' => "La quantità è stata inserita con un formato errato. Riprova"], 422);
                }
            }
    }

    /* metodo getAll(): 
        1. Otteniamo l'array di tutte le vendite effettuate chiamando il salesService.;
        2. Trasformiamo l'array in json con la funzione sendJson, senza statusCode di default 200. */
    public function getAll(){
        $allSales = $this->salesService->getAll();
        sendJson($allSales);
    }

    /* Metodo getById():
        1. In ingresso abbiamo l'id della vendita che vogliamo visualizzare;
        2. Otteniamo la vendita scelta chiamando il metodo dal salesService;
        3. Trasformiamo l'array in json con la funzione sendJson. */
    public function getById(int $id_sale){
        $sale = $this->salesService->getById($id_sale);
        sendJson($sale);
    }

    /* Metodo create():
    1. Effettuiamo un try/catch per la gestione degli errori ed eccezioni;
    2. Recuperiamo i dati inviati dal client con la funzione readJsonInput();
    3. Avviamo la funzione privata validateArray() per la validazione dell'array in ingresso;
    4. Chiamiamo il metodo create e inviamo un messaggio di successo;
    5. Se c'è un errore si entra nel catch, lo estraiamo e lo mandiamo al client. */
    public function create(){
        try {
            $details = readJsonInput();
            $this->validateArray($details);
            $id_sale = $this->salesService->create($details);
            sendJson(['message' => "La vendita n. " . $id_sale . " è stata creata con successo."], 201);
        } catch (Throwable $error) {
            $messageError = $error->getMessage();
            sendJson(['error' => $messageError], $error->getCode());
        }
    }

    /* Metodo delete():
        1. Otteniamo il risultato dell'eliminazione della vendita chiamando il metodo dal salesService;
        2. Gestiamo la casistica 1 Record eliminato;
        3. Gestiamo la casistica 0 non eliminato;
        4. Gestiamo eventuali errori nel catch. */
    public function delete(int $id_sale){
        try {
            $deletedRow = $this->salesService->delete($id_sale);
            if ($deletedRow === 1) {
                sendJson(['message' => "La vendita n. " . $id_sale . " è stata eliminata con successo."], 200);
            } elseif ($deletedRow === 0){
                sendJson(['errore' => "Errore nella cancellazione della vendita n. " . $id_sale . ", la vendita non esiste."], 422);
            }
        } catch (Throwable $error) {
            $messageError = $error->getMessage();
            sendJson(['error' => $messageError], $error->getCode());
        }
    }

    /* Metodo addDetails():
        1. Effettuiamo un try/catch per la gestione degli errori ed eccezioni;
        2. Recuperiamo i dati in ingresso dal client;
        3. Avviamo la funzione privata validateArray() per la validazione dell'array in ingresso;
        4. Gestiamo il caso che l'aggiornamento sia andato a buon fine 1;
        4. Gestiamo il caso che la vendita non esista 0; 
        5. Gestiamo eventuali errori nel catch. */
    public function addDetails(int $id_sale){
        try {
            $details = readJsonInput();
            $this->validateArray($details);
            $resultUpdate = $this->salesService->addDetails($id_sale, $details);
            if ($resultUpdate === 1) {
                sendJson(['message' => "La vendita n. " . $id_sale . " è stata aggiornata con successo"], 200);
            } elseif ($resultUpdate === 0){
                sendJson(['erroe' => "Errore nell'aggiornamento della vendita n. " . $id_sale . "."], 422);
            }
        } catch (Throwable $error) {
            $messageError = $error->getMessage();
            sendJson(['error' => $messageError], $error->getCode());
        }
    }

    /* Metodo deleteDetails():
      1. Facciamo partire il metodo del Service e possiamo ottenere 3 distinti esiti;
      2. Gestiamo la casistica false per la vendita che non esiste;
      3. Gestiamo la casistica 0 la vendita esiste ma il suo dettaglio no;
      4. Gestiamo la casistica 1 per l'eliminazione corretta di un dettaglio;
      5. Gestiamo la casistica 2 per l'eliminazione corretta di un ultimo dettaglio e della vendita stessa di conseguenza;
      6. gestiamo i vari errori nel catch. */
    public function deleteDetail(int $id_sale, int $id_detail){
        try {
            $resultDelete = $this->salesService->deleteDetail($id_sale, $id_detail);
            if ($resultDelete === false) {
                sendJson(['error' => "La vendita n." . $id_sale . " non esiste."], 404);
            } elseif ($resultDelete === 0) {
                sendJson(['error' => "Il dettaglio non è presente all'interno della vendita n." . $id_sale . "."], 404);
            } elseif ($resultDelete === 1) {
                sendJson(['message' => "Il dettaglio n." . $id_detail . " è stato eliminato correttamente dalla vendita n." . $id_sale . "."]);
            } elseif ($resultDelete === 2) {
                sendJson(['message' => "Il dettaglio n." . $id_detail . " e la vendita n." . $id_sale . " sono state eliminate."]);
            }
        } catch (Throwable $error) {
            $messageError = $error->getMessage();
            sendJson(['error' => $messageError], $error->getCode());
        }
    }

    /* Metodo getTotalTimeSaved():
        1. Effettuiamo il try/catch per la gestione degli errori;
        2. Facciamo partire il metodo dedicato dal salesService;
        3. Creiamo una catena di if / else if / else con tutte le 8 combinazioni e creiamo il messaggio e
            gestiamo le casistiche in base ai parametri presenti;
        4. Con sendJson rispondiamo alla richiesta con il n. del tempo totale rispamriato e il messaggio appropriato al caso;
        5. Nel catch estraiamo il messaggio di errore e lo mandiamo come risposta, con getCode() recuperiamo lo 
            statusCode da ogni eccezione. */
    public function getTotalTimeSaved(?string $id_prestation, ?string $start_date, ?string $end_date) {
        try {
            $totalTimeSaved = $this->salesService->getTotalTimeSaved($id_prestation, $start_date, $end_date);

            if ($start_date !== null && $end_date !== null && $id_prestation !== null) {
                $message = 'Il tempo totale risparmiato è di ' . $totalTimeSaved . ' minuti dal ' . $start_date . ' al ' . $end_date . ' per la prestazione n. ' . $id_prestation;
            } elseif ($start_date !== null && $id_prestation !== null) {
                $message = 'Il tempo totale risparmiato è di ' . $totalTimeSaved . ' minuti dal ' . $start_date . ' per la prestazione n. ' . $id_prestation;
            } elseif ($id_prestation !== null && $end_date !== null) {
                $message = 'Il tempo totale risparmiato è di ' . $totalTimeSaved . ' minuti prima del ' . $end_date . ' per la prestazione n. ' . $id_prestation;   
            } elseif ($start_date !== null && $end_date !== null) {
                $message = 'Il tempo totale risparmiato è di ' . $totalTimeSaved . ' minuti dal ' . $start_date . ' al ' . $end_date;
            } elseif ($id_prestation !== null) {
                $message = 'Il tempo totale risparmiato è di ' . $totalTimeSaved . ' minuti, per la prestazione n. ' . $id_prestation;
            } elseif ($start_date !== null) {
                $message = 'Il tempo totale risparmiato è di ' . $totalTimeSaved . ' minuti dal ' . $start_date;
            } elseif ($end_date !== null) {
                $message = 'Il tempo totale risparmiato è di ' . $totalTimeSaved . ' minuti prima del ' . $end_date;
            } else {
                $message = 'Il tempo totale risparmiato è di ' . $totalTimeSaved . ' minuti.';
            }

            sendJson(['message' => $message]);
        } catch (Throwable $error) {
            $messageError = $error->getMessage();
            sendJson(['error' => $messageError], $error->getCode());
        }
    }
}