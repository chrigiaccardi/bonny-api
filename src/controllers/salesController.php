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
    1. Recuperiamo i dati inviati dal client con la funzione readJsonInput();
    2. Controlliamo che i campi esistano;
    3. Controlliamo che i campi siano di tipizzazione corretta;
    4. Effettuiamo un try/catch per la gestione degli errori ed eccezioni;
    5. Dentro il try chiamiamo il metodo create e inviamo un messaggio di successo;
    6. Se c'è un errore si entra nel catch, lo estraiamo e lo mandiamo al client. */
    public function create(){
        $details = readJsonInput();

        if (!array_key_exists('id_prestation', $details)) {
            sendJson(['error' => 'Il campo Prestazione è obbligatorio'], 422);
        }

        if (!array_key_exists('quantity', $details)) {
            sendJson(['error' => 'Il campo Quantità è obbligatorio'], 422);
        }

        if (!is_int($details['id_prestation'])) {
            sendJson(['error' => "Impossibile aggiungere la prestazione selezionata. Verifica la selezione e riprova."]);
        }

        if (!is_int($details['quantity'])) {
            sendJson(['error' => "La quantità è stata inserita con un formato errato. Riprova"]);
        }

        try {
            $id_sale = $this->salesService->create($details);
            sendJson(['message' => "La vendita n. " . $id_sale . " è stata creata con successo."], 201);
        } catch (Throwable $error) {
            $messageError = $error->getMessage();
            sendJson(['error' => $messageError], 422);
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
                sendJson(['message' => "La vendita n. " . $id_sale . " è stata eliminata con successo"], 200);
            } elseif ($deletedRow === 0){
                sendJson(['erroe' => "Errore nella cancellazione della vendita n. " . $id_sale . "."], 422);
            }
        } catch (Throwable $error) {
            $messageError = $error->getMessage();
            sendJson(['error' => $messageError], 422);
        }
    }

    /* Metodo updateDetails():
        1. In ingresso riceviamo l'id della vendita da aggiornare;
        2. Recuperiamo i dati in ingresso dal client;
        3. Validiamo i dati controllando che esistano e che siano tipizzati correttamente;
        4. Gestiamo il caso che l'aggiornamento sia andato a buon fine 1;
        4. Gestiamo il caso che la vendita non esista 0; 
        5. Gestiamo eventuali errori nel catch. */
    public function updateDetails(int $id_sale){
        $details = readJsonInput();

        if (!array_key_exists('id_prestation', $details)) {
            sendJson(['error' => 'Il campo Prestazione è obbligatorio'], 422);
        }

        if (!array_key_exists('quantity', $details)) {
            sendJson(['error' => 'Il campo Quantità è obbligatorio'], 422);
        }

        if (!is_int($details['id_prestation'])) {
            sendJson(['error' => "Impossibile aggiungere la prestazione selezionata. Verifica la selezione e riprova."]);
        }

        if (!is_int($details['quantity'])) {
            sendJson(['error' => "La quantità è stata inserita con un formato errato. Riprova"]);
        }

        try {
            $resultUpdate = $this->salesService->updateDetails($id_sale, $details);

            if ($resultUpdate === 1) {
                sendJson(['message' => "La vendita n. " . $id_sale . " è stata aggiornata con successo"], 200);
            } elseif ($resultUpdate === 0){
                sendJson(['erroe' => "Errore nell'aggiornamento della vendita n. " . $id_sale . "."], 422);
            }
        } catch (Throwable $error) {
            $messageError = $error->getMessage();
            sendJson(['error' => $messageError], 422);
        }
    }

    /* Metodo deleteDetails:
    Ragionare il fatto che potrebbero esserci 3 casi:
        1. false in caso che il dettaglio nella vendita non esista;
        2. In caso che il dettaglio venga eliminato con successo;
        3. Il caso che la vendita venga eliminata con successo e che la vendita anche venga eliminata.*/
    

}