<?php
/* Il file salesRepository conterrà i metodi con le query SQL da utilizzare 
per interrogare il DB per le vendite */

namespace Bonny\repositories;

use PDO;

// Creiamo la classe salesRepository
class SalesRepository {
    // Dichiariamo $pdo.
    private PDO $pdo;

    // Creiamo una costante per il nome della tabella così da riutilizzarla
    private const TABLE_NAME = "sales";

    // Usiamo il costruttore per eguagliare il parametro in ingresso con la variabile privata
    public function __construct(PDO $pdo){
        $this->pdo = $pdo;
    }

    // Metodo getAllSales restituisce tutte le vendite effettuate
    function getAll(): array {
        // Query SQL
        $query = "SELECT s.id, s.sale_date, p.name, sd.quantity 
            FROM " . SalesRepository::TABLE_NAME . " s 
            INNER JOIN sales_details sd ON s.id = sd.id_sale 
            INNER JOIN prestations p ON p.id = sd.id_prestation";

        // Prepariamo lo statement e lo eseguiamo
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();

        // Recuperiamo i dati come array associativo
        $allSales = $stmt->fetchAll();

        // Ritorniamo l'array con tutte le vendite
        return $allSales;
    }
}