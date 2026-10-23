<?php
/* Il file index.php è l'entry point dell'intera app.
Ogni richiesta HTTP ricevuta dal server passa da qua.
Il suo compito è quello di leggere l'url della richiesta e capire il metodo HTTP utilizzato. */

/* Con require_once include i file PHP e da la possibilità di utilizzarli.
Utilizziamo __DIR__ per il percorso assoluto. */

use Bonny\controllers\PrestationsController;
use Bonny\controllers\SalesController;
use Bonny\database\Connection;
use Bonny\repositories\PrestationsRepository;
use Bonny\repositories\SalesRepository;
use Bonny\services\PrestationsService;
use Bonny\services\SalesService;

require_once __DIR__ . '/../vendor/autoload.php';

/* Recuperiamo il valore del metodo della richiesta con la variabile superglobale $_SERVER.
$_SERVER è un Array PHP che contiene le informazioni relativa alla richiesta HTTP ricevuta dal server.
Con REQUEST_METHOD recuperiamo il metodo HTTP: "GET", "POST", "PUT", "PATCH" o "DELETE". */
$method = $_SERVER['REQUEST_METHOD'];

/* Recuperiamo il percorso: 
    1. $_SERVER['REQUEST_URI'] recuperiamo il percorso relativo completo e la query string annessa;
    2. parse_url(..., PHP_URL_PATH) recuperiamo solamente il percorso, senza la query string; */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);


/* $db riceve la configurazione restituita da config.php (array associativo con tutti i dati del file .env) */
$db = require __DIR__ . "/../src/Config/config.php";

// Creiamo l'oggetto $connection passando la configurazione al costruttore
$connection = new Connection($db);

// Recuperiamo la connessione $pdo tramite la funzione getConnection
$pdo = $connection->getConnection();

// Creiamo l'oggetto Repository passando pdo
$prestationsRepository = new PrestationsRepository($pdo);
$salesRepository = new SalesRepository($pdo);

// Creiamo l'oggetto Service passando il repository
$prestationsService = new PrestationsService($prestationsRepository);
$salesService = new SalesService($salesRepository);

// Creiamo l'oggetto controller passando il service
$prestationsController = new PrestationsController($prestationsService);
$salesController = new SalesController($salesService);

// Includiamo il routing per poterlo utilizzare
require __DIR__ . "/../src/routes/prestationsRoutes.php";
require __DIR__ . "/../src/routes/salesRoutes.php";
