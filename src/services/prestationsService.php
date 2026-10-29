<?php
/* Il PrestationService è il file che gestisce le validazioni dei dati che arrivano dal PrestationsController,
applica regole di business, decide quale operazione del PrestationsRepository chiamare, può tradurre/gestire determinate eccezioni applicative..
*/

// Inseriamo un namespace per non avere conflitti in futuro e identificare la classe all'interno dell'architettura
namespace Bonny\services;

// Richiamiamo il PrestationsRepository
use Bonny\repositories\PrestationsRepository;
use InvalidArgumentException;

class PrestationsService{
    // Dichiariamo una proprietà $prestationsRepository affinchè ogni PrestationsService abbia la sua proprietà privata
    private PrestationsRepository $prestationsRepository;

    /* Con il costruttore dichiariamo che l'oggetto di tipo PrestationsRepository
     in ingresso sia inserito nella proprietà privata dell'oggetto $PrestationsRepository */
    public function __construct(PrestationsRepository $prestationsRepository) {
        $this->prestationsRepository = $prestationsRepository;
    }

    //Creiamo i metodi di validazione al di fuori deli metodi così da porterli riutilizzare

    /* Metodo privato validateName(), per validare che il nome della prestazione sia presente e sotto i 50 caratteri:
        1. Puliamo la stringa in ingresso dagli spazi ant e post con trim();
        2. Se il campo è vuoto, mb_strlen restituisce il numero di caratteri presenti, allora creiamo un eccezione;
        3. Se il campo ha più di 50 caratteri, mb_strlen restituisce il numero di caratteri presenti, allora creiamo un eccezione;
        4. Ritorniamo il valore salvato all'inizio visto che ha passato le validazioni.   */
    private function validateName(string $name):string {
        $nameData = trim($name);

        if(mb_strlen($nameData) === 0){
            throw new InvalidArgumentException("Il campo 'Name' è obbligatorio", 422);
        }

        if(mb_strlen($nameData) > 50){
            throw new InvalidArgumentException("Il campo 'Name' non può superare 50 caratteri.", 422);
        }

        return $nameData;
    }

    /* Metodo privato validateTimeSAved(), valida che il tempo rispamriato non sia inferiore a 0.
        Lo 0 lo valutiamo come valore valido (niente risparmio);
        1. Se il tempo in ingresso è minore di 0 allora mandiamo eccezione. */
    private function validateTimeSaved(int $time_saved):void {
        if ($time_saved < 0) {
            throw new InvalidArgumentException('Il Tempo risparmiato non può essere inferiore di 0.', 422);
        }
    }

    /* Metodo privato validateId(), valida che l'id inserito non sia inferiore a 1.
        1. Se l'ID in ingresso è minore di 1 allora mandiamo eccezione. */
    private function validateId(int $id):void {
        if ($id < 1) {
            throw new InvalidArgumentException("L'ID inserito non è valido.", 422);
        }
    }

    /* Metodo getAllActive():array, non ha validazione visto che non ci sono dati in ingresso:
        1. Chiamiamo il metodo dal repository e salviamo il risultato;
        2. Ritorniamo il risultato. */
    public function getAllActive():array {
        $prestationsActive = $this->prestationsRepository->getAllActive();
        return $prestationsActive;
    }

    // Validazione Metodo create(string $name, int $time_saved):int
    public function create(string $name, int $time_saved):int {
        // Chiamiamo le due funzioni di validazione
        $nameData = $this->validateName($name);
        $this->validateTimeSaved($time_saved);

        //Chiamiamo PrestationRepository e il suo metodo create
        $id_prestation = $this->prestationsRepository->create($nameData, $time_saved);

        // Ritorniamo l'id_prestation che arriva dal metodo create del Repository
        return $id_prestation;
    }

    // Validazione metodo update(int $id_prestation, string $name, int $time_saved)
    public function update(int $id_prestation, string $name, int $time_saved):int|false {
        // Validiamo i parametri in ingresso
        $this->validateId($id_prestation);
        $nameData = $this->validateName($name);
        $this->validateTimeSaved($time_saved);

        // Verifichiamo l'esistenza dell'id_prestation in ingresso
        if ($this->prestationsRepository->existsById($id_prestation)) {
            // Eseguiamo il metodo update() del repository e ritorniamo il numero di record aggiornati
            $row = $this->prestationsRepository->update($id_prestation, $nameData, $time_saved);
            return $row;
        }
        return false;
    }

    // Validazione metodo deactivate(int $id_prestation)
    public function deactivate(int $id_prestation):int|false {
        // Validazione del parametro ID
        $this->validateId($id_prestation);

        // Verifichiamo l'esistenza dell'id_prestation in ingresso
        if ($this->prestationsRepository->existsById($id_prestation)) {
            // Eseguiamo il metodo update() del repository e ritorniamo il numero di record aggiornati
            $row = $this->prestationsRepository->deactivate($id_prestation);
            return $row;
        }
        return false;
    }

    // Validazione metodo activate(int $id_prestation)
    public function activate(int $id_prestation): int|false
    {
        // Validazione del parametro ID
        $this->validateId($id_prestation);

        // Verifichiamo l'esistenza dell'id_prestation in ingresso
        if ($this->prestationsRepository->existsById($id_prestation)) {
            // Eseguiamo il metodo update() del repository e ritorniamo il numero di record aggiornati
            $row = $this->prestationsRepository->activate($id_prestation);
            return $row;
        }
        return false;
    }

}