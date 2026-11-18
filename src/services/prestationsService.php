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
    /* Utilizziamo il constructor property promotion per abbreviare il codice e automaticamente
    creare la variabile con l'oggetto associato*/
    public function __construct(readonly PrestationsRepository $prestationsRepository) {}

    //Creiamo i metodi di validazione al di fuori deli metodi così da porterli riutilizzare

    // Metodo privato validateName(), per validare che il nome della prestazione sia presente e sotto i 50 caratteri:
    private function validateName(string $name):string {
        // 1. Puliamo la stringa in ingresso dagli spazi ant e post con trim();
        $nameData = trim($name);

        // 2. Se il campo è vuoto, mb_strlen restituisce il numero di caratteri presenti, allora creiamo un eccezione;
        if(mb_strlen($nameData) === 0){
            throw new InvalidArgumentException("Il campo 'Name' è obbligatorio", 422);
        }

        // 3. Se il campo ha più di 50 caratteri, mb_strlen restituisce il numero di caratteri presenti, allora creiamo un eccezione;
        if(mb_strlen($nameData) > 50){
            throw new InvalidArgumentException("Il campo 'Name' non può superare 50 caratteri.", 422);
        }

        //  4. Ritorniamo il valore salvato all'inizio visto che ha passato le validazioni. 
        return $nameData;
    }

    /* Metodo privato validateTimeSAved(), valida che il tempo rispamriato non sia inferiore a 0.
        Lo 0 lo valutiamo come valore valido (niente risparmio); */
    private function validateTimeSaved(int $time_saved):void {
        // 1. Se il tempo in ingresso è minore di 0 allora mandiamo eccezione.
        if ($time_saved < 0) {
            throw new InvalidArgumentException('Il Tempo risparmiato non può essere inferiore di 0.', 422);
        }
    }

    // Metodo privato validateId(), valida che l'id inserito non sia inferiore a 1.
    private function validateId(int $id):void {
        // 1. Se l'ID in ingresso è minore di 1 allora mandiamo eccezione.
        if ($id < 1) {
            throw new InvalidArgumentException("L'ID inserito non è valido.", 422);
        }
    }

    // Metodo getAllActive():array, non ha validazione visto che non ci sono dati in ingresso:
    public function getAllActive():array {
        // 1. Chiamiamo il metodo dal repository e salviamo il risultato;
        $prestationsActive = $this->prestationsRepository->getAllActive();

        // 2. Ritorniamo il risultato.
        return $prestationsActive;
    }

    // Validazione Metodo create():int, valida il nome e il tempo risparmiato in ingresso:
    public function create(string $name, int $time_saved):int {
        // 1. Validiamo il nome in ingresso e salva il valore essendoci una modifica;
        $nameData = $this->validateName($name);

        // 2. Validiamo il tempo risparmiato in ingresso;
        $this->validateTimeSaved($time_saved);

        // 3. Chiamiamo il metodo dal repository e salviamo il valore ID di ritorno;
        $id_prestation = $this->prestationsRepository->create($nameData, $time_saved);

        // 4. Ritorniamo l'id della vendita appena creata.
        return $id_prestation;
    }

    // Validazione metodo update():int|false, validiamo i 3 parametri in ingresso e ritorniamo l'aggiornamento effettuato:
    public function update(int $id_prestation, string $name, int $time_saved):int|false {
        // 1. Validiamo l'id che arriva in ingresso;
        $this->validateId($id_prestation);

        // 2. Validiamo il nome in ingresso e salviamo il valore visto che c'è stata una modifica;
        $nameData = $this->validateName($name);

        // 3. Validiamo il tempo risparmiato in ingresso;
        $this->validateTimeSaved($time_saved);

        /*  4. Verifichiamo che l'id_prestation esista:
            4.1. Se esiste allora chiamiamo il metodo dal repository;
            4.2. Ritorniamo il valore; */
        if ($this->prestationsRepository->existsById($id_prestation)) {
            $row = $this->prestationsRepository->update($id_prestation, $nameData, $time_saved);
            return $row;
        }
        // 5. Se l'id non esiste ritorniamo false.
        return false;
    }

    // Validazione metodo deactivate():int|false, Validiamo l'id in ingresso e se esista chiamiamo il metodo:
    public function deactivate(int $id_prestation):int|false {

        // 1. Validiamo il parametro ID in ingresso;
        $this->validateId($id_prestation);

        /* 2. Se l'ID selezionato esiste:
                2.1. Eseguiamo la chiamata del metodo dal repository passando ID;
                2.2 Ritorniamo il valore; */
        if ($this->prestationsRepository->existsById($id_prestation)) {
            $row = $this->prestationsRepository->deactivate($id_prestation);
            return $row;
        }

        // 3. Se l'ID selezionato non esiste ritorniamo false.
        return false;
    }

    // Validazione metodo activate(), :int|false, Validiamo l'id in ingresso e se esista chiamiamo il metodo:
    public function activate(int $id_prestation): int|false {
        // 1. Validiamo il parametro ID in ingresso;
        $this->validateId($id_prestation);
        /* 2. Se l'ID selezionato esiste:
                2.1. Eseguiamo la chiamata del metodo dal repository passando ID;
                2.2 Ritorniamo il valore; */
        if ($this->prestationsRepository->existsById($id_prestation)) {
            $row = $this->prestationsRepository->activate($id_prestation);
            return $row;
        }

        // 3. Se l'ID selezionato non esiste ritorniamo false.
        return false;
    }

}