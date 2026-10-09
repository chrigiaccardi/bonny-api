<?php
/* Il prestationController deve: leggere la Request HTTP; capire quale operazione è richiesta;
estrarre id, body JSON, ecc.; chiamare il Service; trasformare il risultato in una Response HTTP JSON;
gestire le eccezioni applicative e assegnare lo status HTTP appropriato. */

// Utilizziamo declare() per rendere il file in modalità strict e generare TypeError
declare(strict_types = 1);

// Inseriamo un namespace per non avere conflitti in futuro e identificare la classe all'interno dell'architettura
namespace Bonny\controllers;

use Bonny\services\PrestationsService;
use InvalidArgumentException;
use TypeError;

class PrestationsController {
    // Dichiariamo una proprietà $prestationsService affinchè ogni PrestationsService abbia la sua proprietà privata
    private PrestationsService $prestationsService;

    /* Con il costruttore dichiariamo che l'oggetto di tipo PrestationsService
     in ingresso sia inserito nella proprietà privata dell'oggetto $PrestationsService */
    public function __construct(PrestationsService $prestationsService) {
        $this->prestationsService = $prestationsService;
    }

    // Metodo getAllActive()
    public function getAllActive(){
        // Otteniamo l'array di tutte le prestazioni attive dal prestationsService
        $prestationsActive = $this->prestationsService->getAllActive();

        // Trasformiamo l'array in Json con la funzione sendJson.
        sendJson($prestationsActive);
    }

    // Metodo create()
    public function create(){
        // Recuperiamo i dati inviati dal client (Json nel body della request) con la funzione readJsonInput()
        $data = readJsonInput();

        /* Controlliamo che i campi name e time_saved siano validi,
        se sono vuoti allora manda un messaggio di errore */
        if (!array_key_exists('name', $data)) {
            sendJson(['error' => "Il campo 'Name' è obbligatorio"], 422);
        }
        /* Se il campo è valido esce dall'if e lo verifichiamo che sia effettivamente una stringa di testo,
        altrimenti mandiamo indietro un messaggio di errore */
        if (is_string($data['name'])) {
            $name = $data['name'];
        } else {
            sendJson(['error' => "Il campo 'Name' deve essere una stringa di testo"], 422);
        }

        if (!array_key_exists('time_saved', $data)) {
            sendJson(['error' => "Il campo 'Time_saved' è obbligatorio."], 422);
        }
        /* Se il campo è valido esce dall'if e lo verifichiamo che sia effettivamente una numero intero,
        altrimenti mandiamo indietro un messaggio di errore */
         if (is_int($data['time_saved'])) {
            $time_saved = $data['time_saved'];
        } else {
            sendJson(['error' => "Il campo 'time_saved' deve essere un numero intero"], 422);
        }
        
        // Chiamiamo il metodo create() dal PrestationsService e gestiamo gli errori con catch
        try {
            $id_prestation = $this->prestationsService->create($name, $time_saved);

            // Inviamo il Json di risposta al client
            sendJson(['message' => 'Prestazione Creata', 'id' => $id_prestation], 201);
        } catch (InvalidArgumentException $error) {
            // Estraiamo il messaggio di errore
            $messageError = $error->getMessage();

            // Inseriamo il messaggio di errore nel json con il codestatus 422
            sendJson(['error' => $messageError], 422);
        }
    }

}