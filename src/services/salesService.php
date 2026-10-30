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
    // Dichiariamo una proprietà $salesRepository affinchè ogni salesService abbia la sua proprietà privata
    private SalesRepository $salesRepository;

    /* Con il costruttore dichiariamo che l'oggetto di tipo PrestationsRepository
     in ingresso sia inserito nella proprietà privata dell'oggetto $PrestationsRepository */
    public function __construct(SalesRepository $salesRepository)
    {
        $this->salesRepository = $salesRepository;
    }

    /* Metodo privato validateId(), valida che l'id inserito non sia inferiore a 1.
        1. Se l'ID in ingresso è minore di 1 allora mandiamo eccezione. */
    private function validateId(int $id): void {
        if ($id < 1) {
            throw new InvalidArgumentException("L'ID inserito non è valido.", 422);
        }
    }
    /* Metodo validateIntId(), Validiamo che l'id sia un intero valido:
        1. In ingresso abbiamo un ID in formato stringa;
        2. Verifichiamo con filter_var(ID, FILTER_VALIDATE_INT) che il valore contenuto in ID sia un intero valido;
        3. Se la verifica è false allora mandiamo un eccezione;
        4. Se true e passa allora trasformiamo la stringa in intero con intval();
        5. Ritorniamo il numero intero. */
    private function validateIntID(string $id): int {
        if (filter_var($id, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("L'ID inserito non è valido.", 422);
        } else {
            $idInt = intval($id);
            return $idInt;
        }
    }

    /* Metodo validateDetails(), Validiamo che l'array, in ingresso, non sia vuoto:
        1. Se l'array $details è vuoto allora mandiamo eccezione. */
    private function validateDetails(array $details): void {
        if ($details === []) {
            throw new InvalidArgumentException("l'elenco dei dettagli della vendita è vuoto.", 404);
        }
    }

    /* Validiamo i singoli elementi dell'array: I valori dell'elemento devono essere validi (id intero positivo e quantità >= 1)
        e controlliamo che non ci siano duplicazioni di dettagli:
        1. Inizializziamo un array vuoto degli id_prestation presente in ogni dettaglio;
        2. Utilizziamo ill foreach per esaminare ogni dettaglio nell'array dettagli;
            2.1. Estraiamo il valore dell'id_prestation;
            2.2. Mandiamo un'eccezione se l'id non è un intero o se è minore di 1;
            2.3. Mandiamo un'eccezione se la quantità non è un intero o se è minore di 1;
            2.4. Verifichiamo che non ci siano presenti duplicati di prestazioni nel singolo dettaglio, in_array verifica che
                il valore sia presente nell'array specificato e true specifica di verificare anche la tipizzazione;
                Se ci sono già prestazioni con lo stesso ID allora mandiamo eccezione;
            2.5 Altrimenti inseriamo l'id dentro l'array $prestationsIds. */
    private function validateElementsDetails(array $details): void {
        $prestationsIds = [];

        foreach ($details as $detail) {
            $id_prestation = $detail['id_prestation'];

            if (!is_int($id_prestation) || $id_prestation < 1) {
                throw new InvalidArgumentException("L'ID non e' valido, inserire valore maggiore o uguale a 1.", 422);
            }

            if (!is_int($detail['quantity']) || $detail['quantity'] < 1) {
                throw new InvalidArgumentException("La quantità inserita deve essere maggiore o uguale a 1.", 422);
            }

            if (in_array($id_prestation, $prestationsIds, true)) {
                throw new InvalidArgumentException("DUPLICAZIONE: La prestazione ID " . $id_prestation . " è già stata inserita.", 422);
            } else {
                $prestationsIds[] = $id_prestation;
            }
        }
    }

    /* Validiamo le date in ingresso, che siano nel formato giusto e che siano reali:
        1. Abbiamo la data in formato stringa in ingresso che arriva dall'url;
        2. Definiamo il pattern che deve avere la data;
        3. con preg_match confrontiamo il pattern con la data ed estraiamo i valori numerici;
        4. Se il match tra pattern e data è OK allora salviamo i valori numerici in $day, $month e $year;
        5. Invece se il match è errato o la data, con checkdate() non esiste mandiamo eccezione.*/
    private function validateDate(string $date)
    {
        $pattern = "#^(\d{4})-(\d{2})-(\d{2})$#";
        $resultDate = preg_match($pattern, $date, $matches);
        if ($resultDate) {
            $year = $matches[1];
            $month = $matches[2];
            $day = $matches[3];
        }

        if (!$resultDate || !checkdate($month, $day, $year)) {
            throw new InvalidArgumentException("La data è stata inserita in un formato errato o non esiste", 422);
        }
    }

    /* Metodo validateDateRange(), Validiamo l'intervallo di date che sia veritiero:
        1. Se la data di inizio e maggiore di quella di fine allora mandiamo una eccezione. (Essendo stringa si possono fare le comparazione con l'operatore matematico.) */
    private function validateDateRange(string $start_date, string $end_date)
    {
        if ($start_date > $end_date) {
            throw new InvalidArgumentException("La data di fine impostata è minore rispatto alla data di inizio.", 422);
        }
    }

    /* Validazione metodo getAll():
        1. chiamiamo il metodo getAll() dal repository;
        2. Ritorniamo l'array ricevuto dal repository. */
    public function getAll(): array
    {
        $allSales = $this->salesRepository->getAll();
        return $allSales;
    }

    /* Validazione metodo getById():
        1. Effettuiamo la validazione dell'id in ingresso;
        2. Chiamiamo il metodo con  getById(int $id_sale)
        3. Ritorniamo l'array ricevuto dal repository, se vuoto ritorna un array vuoto*/
    public function getById(int $id_sale): array
    {
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
    public function create(array $details): int
    {
        $this->validateDetails($details);
        $this->validateElementsDetails($details);
        $id_sale = $this->salesRepository->create($details);
        return $id_sale;
    }

    /* Validazione metodo delete():
        1. Validiamo l'id in ingresso;
        2. Chiamiamo il metodo delete() dal salesRepository;
        3. Ritorniamo il record eliminato 1 oppure non eliminato 0. */
    public function delete(int $id_sale): int
    {
        $this->validateId($id_sale);
        $deletedRow = $this->salesRepository->delete($id_sale);
        return $deletedRow;
    }

    /* Validazione metodo addDetails():
        1. Validazione id_sale in ingresso;
        2. Validazione array details che non sia vuoto;
        3. Validazione degli elementi dell'array details;
        4. Chiamiamo il metodo addDetails dal salesREpository;
        5. Ritorniamo il risultato per l'avvenuto aggiornamento: 1 vendita aggiornata, 0 vendita non presente. */
    public function addDetails(int $id_sale, array $details): int
    {
        $this->validateId($id_sale);
        $this->validateDetails($details);
        $this->validateElementsDetails($details);
        $resultUpdate = $this->salesRepository->addDetails($id_sale, $details);
        return $resultUpdate;
    }

    /* Validazione metodo deleteDetail():
        1. Validazione id_sale in ingresso;
        2. Validazione id_detail in ingresso;
        3. Chiamiamo il metodo deleteDetail dal salesRepository;
        4. Ritorniamo il risultato: 1 per avvenuta cancellazione, 0 per dettaglio non presente. */
    public function deleteDetail(int $id_sale, int $id_detail): int|false
    {
        $this->validateId($id_sale);
        $this->validateId($id_detail);
        $resultDelete = $this->salesRepository->deleteDetail($id_sale, $id_detail);
        return $resultDelete;
    }

    /* Validazione metodo getTotalTimeSaved():
        1. Creiamo la variabile $id_prestation_int = null visto che potrebbe non esistere se id_prestation non c'è;
        2. Se id_prestation è diverso da null, trasformazione e validazione dell'id_prestation in ingresso;
        3. Se l'id_prestation non esiste mandiamo eccezione e blocchiamo la prosecuzione del codice;
        4. Se start_date esiste, validazione dello stesso in ingresso;
        5. Se end_date esiste, validazione dello stesso in ingresso;
        6. Se entrambe le date esistono, validiamo l'intervallo tra esse;
        7. Chiamiamo il metodo getTotalTimeSaved dal salesRepository;
        8. Ritorniamo il risultato ottenuto. */
    public function getTotalTimeSaved(?string $id_prestation, ?string $start_date, ?string $end_date): int
    {
        $id_prestation_int = null;
        if ($id_prestation !== null) {
            $id_prestation_int = $this->validateIntID($id_prestation);
            $this->validateId($id_prestation_int);
            if ($this->salesRepository->existsByIdPrestation($id_prestation_int) === false) {
                throw new InvalidArgumentException("La Prestazione ID " . $id_prestation_int . " non esiste. Riprova", 404);
            }
        }
        if ($start_date !== null) {
            $this->validateDate($start_date);
        }
        if ($end_date !== null) {
            $this->validateDate($end_date);
        }
        if ($start_date !== null && $end_date !== null) {
            $this->validateDateRange($start_date, $end_date);
        }

        $totalTimeSaved = $this->salesRepository->getTotalTimeSaved($id_prestation_int, $start_date, $end_date);

        return $totalTimeSaved;
    }
}
