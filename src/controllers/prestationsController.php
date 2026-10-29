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

class PrestationsController {
    // Dichiariamo una proprietà $prestationsService affinchè ogni PrestationsService abbia la sua proprietà privata.
    private PrestationsService $prestationsService;

    /* Con il costruttore dichiariamo che l'oggetto di tipo PrestationsService
     in ingresso sia inserito nella proprietà privata dell'oggetto $PrestationsService. */
    public function __construct(PrestationsService $prestationsService) {
        $this->prestationsService = $prestationsService;
    }


    /* Metodo getAllActive():
        1. Otteniamo l'array di tutte le prestazioni attive dal prestationsService;
        2. Trasformiamo l'array in Json con la funzione sendJson. */
    public function getAllActive(){
        $prestationsActive = $this->prestationsService->getAllActive();
        sendJson($prestationsActive);
    }


    /* Metodo create(): 
        1. Recuperiamo i dati inviati dal client (Json nel body della request) con la funzione readJsonInput();
        2. Controlliamo che i campi name e time_saved siano validi, se sono vuoti allora manda un messaggio di errore;
        3. Se il campo name è valido esce dall'if e lo verifichiamo che sia effettivamente una stringa di testo,
            altrimenti mandiamo indietro un messaggio di errore;
        4. Se il campo time_saved è valido esce dall'if e lo verifichiamo che sia effettivamente una numero intero,
            altrimenti mandiamo indietro un messaggio di errore;
        5. Chiamiamo il metodo create() dal PrestationsService e gestiamo gli errori con catch;
        6. Inviamo il Json di risposta al client;
        7. Nel catch estraiamo il messaggio di errore e inseriamo il messaggio di errore nel json con getCode() per recuperare lo statusCode. */
    public function create(){
        $data = readJsonInput();

        if (!array_key_exists('name', $data)) {
            sendJson(['error' => "Il campo 'Name' è obbligatorio"], 422);
        }
        if (is_string($data['name'])) {
            $name = $data['name'];
        } else {
            sendJson(['error' => "Il campo 'Name' deve essere una stringa di testo"], 422);
        }
        if (!array_key_exists('time_saved', $data)) {
            sendJson(['error' => "Il campo 'Time_saved' è obbligatorio."], 422);
        }
         if (is_int($data['time_saved'])) {
            $time_saved = $data['time_saved'];
        } else {
            sendJson(['error' => "Il campo 'time_saved' deve essere un numero intero"], 422);
        }
        
        try {
            $id_prestation = $this->prestationsService->create($name, $time_saved);
            sendJson(['message' => 'Prestazione creata con successo', 'id' => $id_prestation], 201);
        } catch (InvalidArgumentException $error) {
            $messageError = $error->getMessage();
            sendJson(['error' => $messageError], $error->getCode());
        }
    }


    /* Metodo update(), come parametro abbiamo l'id per identificare la prestazione esatta: 
        1. Recuperiamo i dati inviati dal client (Json nel body della request) con la funzione readJsonInput();
        2. Controlliamo che i campi name e time_saved siano validi, se sono vuoti allora manda un messaggio di errore;
        3. Se il campo name è valido esce dall'if e lo verifichiamo che sia effettivamente una stringa di testo,
            altrimenti mandiamo indietro un messaggio di errore;
        4. Se il campo time_saved è valido esce dall'if e lo verifichiamo che sia effettivamente una numero intero,
            altrimenti mandiamo indietro un messaggio di errore;
        5. Utilizziamo il try/catch per la gestione degli errori/eccezioni;
        6. Chiamiamo il metodo create() dal PrestationsService e salviamo il risultato in $row;
        7. Se vi è 1 riga modificata allora successo allora inviamo il Json di risposta al client che la prestazione è stata aggiornata;
        8. Se 0 riga esistente ma non aggiornata (uguale) inviamo il Json di risposta al client che la prestazione è uguale a prima;
        9. Se false non trovata, inviamo il Json di risposta al client che la prestazione non è stata trovata;
        10. Nel catch estraiamo il messaggio di errore e inseriamo il messaggio di errore nel json con getCode() per recuperare lo statusCode. */
    public function update(int $id_prestation){
        $data = readJsonInput();

        if (!array_key_exists('name', $data)) {
            sendJson(['error' => "Il campo 'Name' è obbligatorio"], 422);
        }
        if (is_string($data['name'])) {
            $name = $data['name'];
        } else {
            sendJson(['error' => "Il campo 'Name' deve essere una stringa di testo"], 422);
        }
        if (!array_key_exists('time_saved', $data)) {
            sendJson(['error' => "Il campo 'Time_saved' è obbligatorio."], 422);
        }
        if (is_int($data['time_saved'])) {
            $time_saved = $data['time_saved'];
        } else {
            sendJson(['error' => "Il campo 'time_saved' deve essere un numero intero"], 422);
        }

        try {
            $row = $this->prestationsService->update($id_prestation, $name, $time_saved);
            if ($row === 1) {
                sendJson(['message' => 'Prestazione aggiornata con successo', 'id' => $id_prestation], 200);
            } elseif ($row === 0) {
                sendJson(['message' => 'Prestazione rimasta invariata', 'id' => $id_prestation], 200);
            } elseif ($row === false) {
                sendJson(['error' => 'Prestazione non trovata'], 404);
            }
        } catch (InvalidArgumentException $error) {
            $messageError = $error->getMessage();
            sendJson(['error' => $messageError], $error->getCode());
        }
    }


    /* Metodo deactivate(), come parametro abbiamo l'id per identificare la prestazione da disattivare:
        1. Utilizziamo il try/catch così da gestire gli errori/eccezioni;
        2. Facciamo partire il metodo deactivate per disattivare la perstazione e salviamo il risultato in $row;
        3. Se vi è 1 riga modificata allora successo, inviamo il Json di risposta al client che la prestazione è stata disattivata;
        4. Se 0 riga esistente ma non aggiornata (uguale) inviamo il Json di risposta al client che la prestazione è uguale a prima;
        5. Se false non trovata, inviamo il Json di risposta al client che la prestazione non è stata trovata;
        6. Nel catch estraiamo il messaggio di errore e inseriamo il messaggio di errore nel json con getCode() per recuperare lo statusCode. */
    public function deactivate(int $id_prestation){
        try {
            $row = $this->prestationsService->deactivate($id_prestation);

            if ($row === 1) {
                sendJson(['message' => 'Prestazione disattivata con successo', 'id' => $id_prestation], 200);
            } elseif ($row === 0) {
                sendJson(['message' => 'Prestazione già impostata su Disattivato', 'id' => $id_prestation], 200);
            } elseif ($row === false) {
                sendJson(['error' => 'Prestazione non trovata'], 404);
            }
        } catch (InvalidArgumentException $error) {
            $messageError = $error->getMessage();
            sendJson(['error' => $messageError], $error->getCode());
        }
    }


    /* Metodo activate(), parametro ID ingresso per selezionare la prestazione da attivare:
        1. Utilizziamo il try/catch così da gestire gli errori/eccezioni;
        2. Facciamo partire il metodo activate per disattivare la prestazione e salviamo il risultato in $row;
        3. Se vi è 1 riga modificata allora successo, inviamo il Json di risposta al client che la prestazione è stata attivata;
        4. Se 0 riga esistente ma non aggiornata (uguale) inviamo il Json di risposta al client che la prestazione è uguale a prima;
        5. Se false non trovata, inviamo il Json di risposta al client che la prestazione non è stata trovata;
        6. Nel catch estraiamo il messaggio di errore e inseriamo il messaggio di errore nel json con getCode() per recuperare lo statusCode. */
    public function activate(int $id_prestation){
        try {
            $row = $this->prestationsService->activate($id_prestation);

            if ($row === 1) {
                sendJson(['message' => 'Prestazione attivata con successo', 'id' => $id_prestation], 200);
            } elseif ($row === 0) {
                sendJson(['message' => 'Prestazione già impostata su Attivo', 'id' => $id_prestation], 200);
            } elseif ($row === false) {
                sendJson(['error' => 'Prestazione non trovata'], 404);
            }
        } catch (InvalidArgumentException $error) {
            $messageError = $error->getMessage();
            sendJson(['error' => $messageError], $error->getCode());
        }
    }
}