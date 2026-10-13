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

/*Andiamo ad effettuare la verifica della condizione per eseguire il giusto metodo */
if ($method === 'GET' && $path === '/prestations') {
    $prestationsController->getAllActive();
}

if ($method === 'POST' && $path === '/prestations') {
    $prestationsController->create();
}


