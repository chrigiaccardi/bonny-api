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
    // istanziamo la variabile $prestationsRepository affinchè ogni PrestationsService abbia la sua proprietà privata
    private PrestationsRepository $prestationsRepository;

    /* Con il costruttore dichiariamo che l'oggetto di tipo PrestationsRepository
     in ingresso sia inserito nella proprietà privata dell'oggetto $PrestationsRepository */
    public function __construct(PrestationsRepository $prestationsRepository) {
        $this->prestationsRepository = $prestationsRepository;
    }

    //Creiamo i metodi di validazione al di fuori deli metodi così da porterli riutilizzare
    private function validateName(string $name):string {
        // trim() elimina spazi all'inizio e alla fine
        $nameData = trim($name);

        // Effettuiamo una verifica, se il campo è vuoto da errore
        if(mb_strlen($nameData) === 0){
            throw new InvalidArgumentException('Il nome è Obbligatorio!');
        }

        // Effettuiamo una verifica che $name sia sotto i 50 caratteri
        if(mb_strlen($nameData) > 50){
            throw new InvalidArgumentException('Il nome non può superare 50 caratteri.');
        }

        // Ritorniamo il name validato
        return $nameData;
    }

    private function validateTimeSaved(int $time_saved):void {
        if ($time_saved < 0) {
            throw new InvalidArgumentException('Il Tempo risparmiato non può essere inferiore di 0.');
        }
    }

    private function validateId(int $id):void {
        if ($id < 1) {
            throw new InvalidArgumentException("L'ID inserito non è valido.");
        }
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
    public function update(int $id_prestation, string $name, int $time_saved):int {
        // Validiamo i parametri in ingresso
        $this->validateId($id_prestation);
        $nameData = $this->validateName($name);
        $this->validateTimeSaved($time_saved);

        // Eseguiamo il metodo update() del repository e ritorniamo il numero di record aggiornati
        $row = $this->prestationsRepository->update($id_prestation, $nameData, $time_saved);
        return $row;
    }

    // Validazione metodo deactivate(int $id_prestation)
    public function deactivate(int $id_prestation):int {
        // Validazione del parametro ID
        $this->validateId($id_prestation);

        // Eseguiamo il metodo deactivate() dal repository e ritorniamo il numero di record disattivati
        $row = $this->prestationsRepository->deactivate($id_prestation);
        return $row;
    }

    // Validazione metodo activate(int $id_prestation)
    public function activate(int $id_prestation): int
    {
        // Validazione del parametro ID
        $this->validateId($id_prestation);

        // Eseguiamo il metodo activate() dal repository e ritorniamo il numero di record attivati
        $row = $this->prestationsRepository->activate($id_prestation);
        return $row;
    }

}