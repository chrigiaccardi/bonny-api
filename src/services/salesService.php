<?php
/* Il SalesService è il file che gestisce le validazioni dei dati che arrivano dal SalesController,
applica regole di business, decide quale operazione del SalesRepository chiamare, 
può tradurre/gestire determinate eccezioni applicative..
*/

namespace Bonny\services;

use Bonny\repositories\SalesRepository;
use DateTime;
use InvalidArgumentException;

class SalesService
{
    /* Utilizziamo il constructor property promotion per abbreviare il codice e automaticamente
    creare la variabile con l'oggetto associato*/
    public function __construct(readonly SalesRepository $salesRepository) {}

    // Metodo privato validateId(), valida che l'id inserito non sia inferiore a 1.
    private function validateId(int $id): void {
        // 1. Se l'ID in ingresso è minore di 1 allora mandiamo eccezione.
        if ($id < 1) {
            throw new InvalidArgumentException("L'ID inserito non è valido.", 422);
        }
    }
    // Metodo validateIntId(), Validiamo che l'id sia un intero valido:
    private function validateIntID(string $id): int {
        // 1. In ingresso abbiamo un ID in formato stringa;
        // 2. Verifichiamo con filter_var(ID, FILTER_VALIDATE_INT) che il valore contenuto in ID sia un intero valido;
        if (filter_var($id, FILTER_VALIDATE_INT) === false) {
            // 3. Se la verifica è false allora mandiamo un eccezione;
            throw new InvalidArgumentException("L'ID inserito non è valido.", 422);
        } else {
            // 4. Se true e passa allora trasformiamo la stringa in intero con intval();
            $idInt = intval($id);
            // 5. Ritorniamo il numero intero.
            return $idInt;
        }
    }

    // Metodo validateDetails(), Validiamo che l'array, in ingresso, non sia vuoto:
    private function validateDetails(array $details): void {
        // 1. Se l'array $details è vuoto allora mandiamo eccezione.
        if ($details === []) {
            throw new InvalidArgumentException("l'elenco dei dettagli della vendita è vuoto.", 404);
        }
    }

    /* Validiamo i singoli elementi dell'array: I valori dell'elemento devono essere validi (id intero positivo e quantità >= 1)
        e controlliamo che non ci siano duplicazioni di dettagli:
        
       */
    private function validateElementsDetails(array $details): void {
        // 1. Inizializziamo un array vuoto degli id_prestation presente in ogni dettaglio;
        $prestationsIds = [];

        // 2. Utilizziamo ill foreach per esaminare ogni dettaglio nell'array dettagli;
        foreach ($details as $detail) {
            // 2.1. Estraiamo il valore dell'id_prestation;
            $id_prestation = $detail['id_prestation'];

            // 2.2. Mandiamo un'eccezione se l'id non è un intero o se è minore di 1;
            if (!is_int($id_prestation) || $id_prestation < 1) {
                throw new InvalidArgumentException("L'ID non e' valido, inserire valore maggiore o uguale a 1.", 422);
            }

            // 2.3. Mandiamo un'eccezione se la quantità non è un intero o se è minore di 1;
            if (!is_int($detail['quantity']) || $detail['quantity'] < 1) {
                throw new InvalidArgumentException("La quantità inserita deve essere maggiore o uguale a 1.", 422);
            }
            /* 2.4. Verifichiamo che non ci siano presenti duplicati di prestazioni nel singolo dettaglio, in_array verifica che
                    il valore sia presente nell'array specificato e true specifica di verificare anche la tipizzazione;
                    Se ci sono già prestazioni con lo stesso ID allora mandiamo eccezione; */
            if (in_array($id_prestation, $prestationsIds, true)) {
                throw new InvalidArgumentException("DUPLICAZIONE: La prestazione ID " . $id_prestation . " è già stata inserita.", 422);
            } else {
                // 2.5 Altrimenti inseriamo l'id dentro l'array $prestationsIds.
                $prestationsIds[] = $id_prestation;
            }
        }
    }

    /* Validiamo le date in ingresso, che siano nel formato giusto e che siano reali:
        
        
        
        
        */
    private function validateDate(string $date) {
        // 1. Abbiamo la data in formato stringa in ingresso che arriva dall'url;
        // 2. Definiamo il pattern che deve avere la data;
        $pattern = "#^(\d{4})-(\d{2})-(\d{2})$#";

        // 3. con preg_match confrontiamo il pattern con la data ed estraiamo i valori numerici;
        $resultDate = preg_match($pattern, $date, $matches);

        // 4. Se il match tra pattern e data è OK allora salviamo i valori numerici in $day, $month e $year;
        if ($resultDate) {
            $year = $matches[1];
            $month = $matches[2];
            $day = $matches[3];
        }

        // 5. Invece se il match è errato o la data, con checkdate() non esiste mandiamo eccezione.
        if (!$resultDate || !checkdate($month, $day, $year)) {
            throw new InvalidArgumentException("La data è stata inserita in un formato errato o non esiste", 422);
        }
    }

    // Metodo validateDateRange(), Validiamo l'intervallo di date che sia veritiero:
        
    private function validateDateRange(string $start_date, string $end_date) {
        /* 1. Se la data di inizio e maggiore di quella di fine allora mandiamo una eccezione.
            (Essendo stringa si possono fare le comparazioni con l'operatore matematico.) */
        if ($start_date > $end_date) {
            throw new InvalidArgumentException("La data di fine impostata è minore rispatto alla data di inizio.", 422);
        }
    }

    // Validazione metodo getAll():
    public function getAll(): array {
        // 1. Chiamiamo il metodo getAll() dal repository;
        $allSales = $this->salesRepository->getAll();
        // 2. Ritorniamo l'array ricevuto dal repository.
        return $allSales;
    }

    // Validazione metodo getById():
    public function getById(int $id_sale): array {
        // 1. Effettuiamo la validazione dell'id in ingresso;
        $this->validateId($id_sale);

        // 2. Chiamiamo il metodo con  getById(int $id_sale)
        $sale = $this->salesRepository->getById($id_sale);

        // 3. Ritorniamo l'array ricevuto dal repository, se vuoto ritorna un array vuoto
        return $sale;
    }

    // Validazione metodo create():
    public function create(array $details): int {
        // 1. Effettuiamo il controllo che l'array in ingresso non sia vuoto;
        $this->validateDetails($details);

        /* 2. I valori dell'elemento devono essere validi (id intero positivo e quantità >= 1)
            e controlliamo che non ci siano duplicazioni di dettagli. */
        $this->validateElementsDetails($details);

        // 3. Lanciamo il metodo create dal salesRepository;
        $id_sale = $this->salesRepository->create($details);

        // 4. Ritorniamo l'id della vendita appena creata.
        return $id_sale;
    }

    // Validazione metodo delete():
    public function delete(int $id_sale): int {
        // 1. Validiamo l'id in ingresso;
        $this->validateId($id_sale);

        // 2. Chiamiamo il metodo delete() dal salesRepository;
        $deletedRow = $this->salesRepository->delete($id_sale);

        // 3. Ritorniamo il record eliminato 1 oppure non eliminato 0.
        return $deletedRow;
    }

    // Validazione metodo addDetails():
    public function addDetails(int $id_sale, array $details): int {
        // 1. Validazione id_sale in ingresso;
        $this->validateId($id_sale);

        // 2. Validazione array details che non sia vuoto;
        $this->validateDetails($details);

        // 3. Validazione degli elementi dell'array details;
        $this->validateElementsDetails($details);

        // 4. Chiamiamo il metodo addDetails dal salesREpository;
        $resultUpdate = $this->salesRepository->addDetails($id_sale, $details);

        // 5. Ritorniamo il risultato per l'avvenuto aggiornamento: 1 vendita aggiornata, 0 vendita non presente.
        return $resultUpdate;
    }

    /* Validazione metodo deleteDetail():
        
        
        
         */
    public function deleteDetail(int $id_sale, int $id_detail): int|false {
        // 1. Validazione id_sale in ingresso;
        $this->validateId($id_sale);

        // 2. Validazione id_detail in ingresso;
        $this->validateId($id_detail);

        // 3. Chiamiamo il metodo deleteDetail dal salesRepository;
        $resultDelete = $this->salesRepository->deleteDetail($id_sale, $id_detail);

        // 4. Ritorniamo il risultato: 1 per avvenuta cancellazione, 0 per dettaglio non presente.
        return $resultDelete;
    }

    // Validazione metodo getTotalTimeSaved():
    public function getTotalTimeSaved(?string $id_prestation, ?string $start_date, ?string $end_date): int {
        // 1. Creiamo la variabile $id_prestation_int = null visto che potrebbe non esistere se id_prestation non c'è;
        $id_prestation_int = null;

        // 2. Se id_prestation è diverso da null, trasformazione e validazione dell'id_prestation in ingresso;
        if ($id_prestation !== null) {
            $id_prestation_int = $this->validateIntID($id_prestation);
            $this->validateId($id_prestation_int);
            // 3. Se l'id_prestation non esiste nel DB mandiamo eccezione e blocchiamo la prosecuzione del codice;
            if ($this->salesRepository->existsByIdPrestation($id_prestation_int) === false) {
                throw new InvalidArgumentException("La Prestazione ID " . $id_prestation_int . " non esiste. Riprova", 404);
            }
        }

        // 4. Se start_date esiste, validazione dello stesso in ingresso;
        if ($start_date !== null) {
            $this->validateDate($start_date);
        }

        // 5. Se end_date esiste, validazione dello stesso in ingresso;
        if ($end_date !== null) {
            $this->validateDate($end_date);
        }

        // 6. Se entrambe le date esistono, validiamo l'intervallo tra esse;
        if ($start_date !== null && $end_date !== null) {
            $this->validateDateRange($start_date, $end_date);
        }

        // 7. Chiamiamo il metodo getTotalTimeSaved dal salesRepository;
        $totalTimeSaved = $this->salesRepository->getTotalTimeSaved($id_prestation_int, $start_date, $end_date);

        // 8. Ritorniamo il risultato ottenuto.
        return $totalTimeSaved;
    }
}
