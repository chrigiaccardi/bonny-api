<?php
/* Il file prestationsRoutes riceve una chiamata HTTP ed, in base
alla rotta, decide quale Controller utilizzare e quale metodo. */

// Inseriamo un namespace per non avere conflitti in futuro e identificare la classe all'interno dell'architettura
namespace Bonny\routes;

use Bonny\controllers\PrestationsController;
use Bonny\database\Connection;
use Bonny\repositories\PrestationsRepository;
use Bonny\services\PrestationsService;

/** @var string $method */
/** @var string $path */

// Tramite regex recuperiamo l'id_prestation da poterlo utilizzare nel metodo update()
// Creiamo il pattern dove (\d+) estrae l'il numero presente
$pattern = "#^/prestations/(\d+)$#";

// con preg_match estraiamo dal path il numero dell'id e lo inseriamo nell'array matches
// l'id_prestation è uguale alla conversione in int del valore 1 dell'array matches
if (preg_match($pattern, $path, $matches) === 1) {
    $id_prestation = intval($matches[1]);
}

/*Andiamo ad effettuare la verifica della condizione per eseguire il giusto metodo */
if ($method === 'GET' && $path === '/prestations') {
    $prestationsController->getAllActive();
}

if ($method === 'POST' && $path === '/prestations') {
    $prestationsController->create();
}

if ($method === 'PUT' && $path === '/prestations/{id}') {
    $prestationsController->update($id_prestation);
}

