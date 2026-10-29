<?php
/* Il file salesRoutes riceve la chiamata HTTP, è in base all'url,
gestisce quale controller chiamare e di conseguenza tutta la catena.*/

// Inseriamo un namespace per non avere conflitti in futuro e identificare la classe all'interno dell'architettura
namespace Bonny\routes;

/** @var string $method */
/** @var string $path */

// Creiamo in condizionali per il richiamo dei vari metodi del salesController
// Metodo getAll():
if($method === 'GET' && $path === '/sales'){
    $salesController->getAll();
}

// Metodo getTotalTimeSaved():
if ($method === 'GET' && $path === '/total-time-saved') {
    $id_prestation = $_GET['id_prestation'] ?? null;
    $start_date = $_GET['start_date'] ?? null;
    $end_date = $_GET['end_date'] ?? null;
    $salesController->getTotalTimeSaved($id_prestation, $start_date, $end_date);
}

/* Per quanto riguarda le routes di con valori dinamici, ci sono più passaggi da fare:
    Tramite regex recuperiamo l'id_prestation da poterlo utilizzare nel metodo update()
    1. Creiamo il pattern dove (\d+) estrae l'il numero presente;
    2. con preg_match estraiamo dal path il numero dell'id e lo inseriamo nell'array matches;
    3. l'id_sales è uguale alla conversione in int del valore 1 dell'array matches;
    4. Chiamiamo il metodo con l'id_sales estratto. */

$pattern1 = "#^/sales/(\d+)$#";
$pattern2 = "#^/sales/(\d+)/details$#";
$pattern3 = "#^/sales/(\d+)/details/(\d+)$#";

// Metodo getByID():
if($method === 'GET' && (preg_match($pattern1, $path, $matches) === 1)){
    $id_sale = intval($matches[1]);
    $salesController->getById($id_sale);
}

// MEtodo create():
if ($method === 'POST' && $path === "/sales") {
    $salesController->create();
}
// Metodo delete():
if ($method === 'DELETE' && (preg_match($pattern1, $path, $matches) === 1)){
    $id_sale = intval($matches[1]);
    $salesController->delete($id_sale);
}

// Metodo addDetails():
if ($method === 'POST' && (preg_match($pattern2, $path, $matches) === 1)){
    $id_sale = intval($matches[1]);
    $salesController->addDetails($id_sale);
}

// Metodo deleteDetail():
if ($method === 'DELETE' && (preg_match($pattern3, $path, $matches) === 1)){
    $id_sale = intval($matches[1]);
    $id_detail = intval($matches[2]);
    $salesController->deleteDetail($id_sale, $id_detail);
}
