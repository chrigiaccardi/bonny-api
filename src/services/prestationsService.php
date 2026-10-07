<?php
/* Il PrestationService è il file che gestisce le validazioni dei dati che arrivano dal PrestationsController,
applica regole di business, decide quale operazione del PrestationsRepository chiamare, può tradurre/gestire determinate eccezioni applicative..
*/

// Inseriamo un namespace per non avere conflitti in futuro e identificare la classe all'interno dell'architettura
namespace Bonny\services;

// Richiamiamo il PrestationsRepository
use Bonny\repositories\PrestationsRepository;

class PrestationsService{
    // istanziamo la variabile $prestationsRepository affinchè ogni PrestationsService abbia la sua proprietà privata
    private PrestationsRepository $prestationsRepository;

    /* Con il costruttore dichiariamo che l'oggetto di tipo PrestationsRepository
     in ingresso sia inserito nella proprietà privata dell'oggetto $PrestationsRepository */
    public function __construct(PrestationsRepository $prestationsRepository) {
        $this->prestationsRepository = $prestationsRepository;
    }

}