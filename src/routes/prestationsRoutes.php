<?php
/* Il file prestationsRoutes riceve una chiamata HTTP ed, in base
alla rotta, decide quale Controller utilizzare e quale metodo. */

// Inseriamo un namespace per non avere conflitti in futuro e identificare la classe all'interno dell'architettura
namespace Bonny\routes;

/** @var string $method */
/** @var string $path */

/*Andiamo ad effettuare la verifica della condizione per eseguire il giusto metodo */
if ($method === 'GET' && $path === '/prestations') {
    $prestationsController->getAllActive();
}

if ($method === 'POST' && $path === '/prestations') {
    $prestationsController->create();
}

/* Per quanto riguarda la route di aggiornamento ci sono più passaggi da fare:
    Tramite regex recuperiamo l'id_prestation da poterlo utilizzare nel metodo update()
    1. Creiamo il pattern dove (\d+) estrae l'il numero presente;
    2. con preg_match estraiamo dal path il numero dell'id e lo inseriamo nell'array matches;
    3. l'id_prestation è uguale alla conversione in int del valore 1 dell'array matches;
    4. Chiamiamo il metodo con l'id_prestation estratto. */
    
$pattern = "#^/prestations/(\d+)$#";

if ($method === 'PUT' && (preg_match($pattern, $path, $matches) === 1)) {
    $id_prestation = intval($matches[1]);
    // Recuperato l'id_prestation lo inseriamo nel metodo update()
    $prestationsController->update($id_prestation);
}

if($method === 'DELETE' && (preg_match($pattern, $path, $matches) === 1)) {
    $id_prestation = intval($matches[1]);
    // Recuperato l'id_prestation lo inseriamo nel metodo update()
    $prestationsController->deactivate($id_prestation);
}

if ($method === 'PATCH' && (preg_match($pattern, $path, $matches) === 1)) {
    $id_prestation = intval($matches[1]);
    $prestationsController->activate($id_prestation);
}

