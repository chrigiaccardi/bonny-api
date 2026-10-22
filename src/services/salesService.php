<?php
/* Il SalesService è il file che gestisce le validazioni dei dati che arrivano dal SalesController,
applica regole di business, decide quale operazione del SalesRepository chiamare, 
può tradurre/gestire determinate eccezioni applicative..
*/
namespace Bonny\services;

use Bonny\repositories\SalesRepository;
use InvalidArgumentException;

class SalesService{
    // Dichiariamo una proprietà $salesRepository affinchè ogni salesService abbia la sua proprietà privata
    private SalesRepository $salesRepository;

    /* Con il costruttore dichiariamo che l'oggetto di tipo PrestationsRepository
     in ingresso sia inserito nella proprietà privata dell'oggetto $PrestationsRepository */
    public function __construct(SalesRepository $salesRepository){
        $this->salesRepository = $salesRepository;
    }

    // Creiamo i metodi di validazione da utilizzare poi nei metodi
    // Validiamo che l'id sia positivo 
    private function validateId(int $id):void {
        if ($id < 1) {
            throw new InvalidArgumentException("L'ID inserito non è valido.");
        }
    }

    // Validiamo che l'array non sia vuoto in ingresso
    private function validateDetails(array $details):void {
        if ($details === []) {
            throw new InvalidArgumentException("l'elenco dei dettagli della vendita è vuoto.");
            
        }
    }
    /* Validiamo i singoli elementi dell'array: I valori dell'elemento devono essere validi (id intero positivo e quantità >= 1)
    e controlliamo che non ci siano duplicazioni di dettagli. */
    private function validateElementsDetails(array $details):void {
        // Inizializziamo un array vuoto degli id_prestation presente in ogni dettaglio
        $prestationsIds = [];

        foreach($details as $detail){
            // Estraiamo il valore dal dettaglio
            $id_prestation = $detail['id_prestation'];

            // Mandiamo un'eccezione se l'id non è un intero o se è minore di 1
            if (!is_int($id_prestation) || $id_prestation < 1) {
                throw new InvalidArgumentException("L'ID non è valido, inserire valore maggiore o uguale a 1.");
            }

            // Mandiamo un'eccezione se la quantità non è un intero o se è minore di 1
            if (!is_int($detail['quantity']) || $detail['quantity'] < 1) {
                throw new InvalidArgumentException("La quantità inserita deve essere maggiore o uguale a 1.");
            }
            
            // Verifichiamo che non ci siano presenti duplicati di prestazioni nel singolo dettaglio
            // in_array verifica che il valore sia presente nell'array specificato e con true la tipizzazione
            if (in_array($id_prestation, $prestationsIds, true)){
                throw new InvalidArgumentException("La prestazione ID " . $id_prestation . " è già stata inserita.");
            } else {
                // Inseriamo l'id nell'array così che al prossimo giro sia presente
                $prestationsIds[] = $id_prestation;
            }
        }
    }

    /* Validazione metodo getAll():
        1. chiamiamo il metodo getAll() dal repository;
        2. Ritorniamo l'array ricevuto dal repository. */
    public function getAll():array {
        $allSales = $this->salesRepository->getAll();
        return $allSales;
    }

    /* Validazione metodo getById():
        1. Effettuiamo la validazione dell'id in ingresso;
        2. Chiamiamo il metodo con  getById(int $id_sale)
        3. Ritorniamo l'array ricevuto dal repository, se vuoto ritorna un array vuoto*/
    public function getById(int $id_sale):array {
        $this->validateId($id_sale);
        $sale = $this->salesRepository->getById($id_sale);
        return $sale;
    }

    /* Validazione metodo create():
        1. Effettuiamo il controllo che l'array in ingresso non sia vuoto;
        2. I valori dell'elemento devono essere validi (id intero positivo e quantità >= 1)
            e controlliamo che non ci siano duplicazioni di dettagli.
        3. Lanciamo il metodo create dal salesRepository;
        4. Ritorniamo l'id della vendita appena creata. */
    public function create(array $details):int {
        $this->validateDetails($details);
        $this->validateElementsDetails($details);
        $id_sale = $this->salesRepository->create($details);
        return $id_sale;
    }

    /* Validazione metodo delete():
        1. Validiamo l'id in ingresso;
        2. Chiamiamo il metodo delete() dal salesRepository;
        3. Ritorniamo il record eliminato 1 oppure non eliminato 0. */
    public function delete(int $id_sale):int {
        $this->validateId($id_sale);
        $deletedRow = $this->salesRepository->delete($id_sale);
        return $deletedRow;
    }

    /* Validazione metodo updateDetails():
        1. Validazione id_sale in ingresso;
        2. Validazione array details che non sia vuoto;
        3. Validazione degli elementi dell'array details;
        4. Chiamiamo il metodo updateDetails dal salesREpository;
        5. Ritorniamo il risultato per l'avvenuto aggiornamento: 1 vendita aggiornata, 0 vendita non presente. */
    public function updateDetails(int $id_sale, array $details):int {
        $this->validateId($id_sale);
        $this->validateDetails($details);
        $this->validateElementsDetails($details);
        $resultUpdate = $this->salesRepository->updateDetails($id_sale, $details);
        return $resultUpdate;
    }

    /* Validazione metodo deleteDetail():
        1. Validazione id_sale in ingresso;
        2. Validazione id_detail in ingresso;
        3. Chiamiamo il metodo deleteDetail dal salesRepository;
        4. Ritorniamo il risultato: 1 per avvenuta cancellazione, 0 per dettaglio non presente. */
    public function deleteDetail(int $id_sale, int $id_detail):int|false {
        $this->validateId($id_sale);
        $this->validateId($id_detail);
        $resultDelete = $this->salesRepository->deleteDetail($id_sale, $id_detail);
        return $resultDelete;
    }
}