<?php
/* Il file helpers.php si occupa della gestione dei file json, un metodo
per leggerlo e un metodo per inviarlo così che siano
 riutilizzabili nei controller*/

/* La funzione sendJson() serve per agevolarci con il codice per la creazione del file json:
In in gresso abbiamo i dati che possono essere misti e il parametro per lo status code che,
se non indicato sarà 200 OK */

function sendJson(mixed $data, int $statusCode = 200): void {
    // Impostiamo l'header come application/json per il body della risposta
    header('Content-Type: application/json');

    // Impostiamo il codice status di risposta come quello in ingresso della funzione
    http_response_code($statusCode);
    
    // L'array PHP in ingresso viene trasformato in una json string ed echo invia effettivamente il json
    echo json_encode($data);

    // Exit dichiara che la richiesta è conclusa, quindi di non eseguire più codice.
    exit;
}

/* La funzione readJsonInpput() decodifica il Json string e con true lo trasformiamo in Array associativo,
ritorniamo o i dati che andranno al Controller oppure un array vuoto*/
function readJsonInput(): array {
    // $data è l'array associativo decodificato e trasformato dalla stringa json in entrata con la richiesta
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Ritorniamo $data oppure un array vuoto
    return $data ?? [];
}
