<?php
/* Il file prestationsRoutes riceve una chiamata HTTP ed, in base
alla rotta, decide quale Controller utilizzare e quale metodo. */

// Inseriamo un namespace per non avere conflitti in futuro e identificare la classe all'interno dell'architettura
namespace Bonny\routes;

use Bonny\controllers\PrestationsController;
use Bonny\database\Connection;
use Bonny\repositories\PrestationsRepository;
use Bonny\services\PrestationsService;

/* $db riceve la configurazione restituita da config.php
 (array associativo con tutti i dati del file .env) */

$db = require __DIR__ . "/../Config/config.php";

// Creiamo l'oggetto $connection passando la configurazione al costruttore
$connection = new Connection($db);

// Recuperiamo la connessione $pdo tramite la funzione getConnection
$pdo = $connection->getConnection();

// Creiamo l'oggetto Repository passando pdo
$prestationsRepository = new PrestationsRepository($pdo);

// Creiamo l'oggetto Service passando il repository
$prestationsService = new PrestationsService($prestationsRepository);

// Creiamo l'oggetto controller passando il service
$prestationsController = new PrestationsController($prestationsService);
