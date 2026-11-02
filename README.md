# Bonny-Api ⏱️ - RESTful API per la visualizzazione del tempo risparmiato che utilizzano determinati servizi

## 🗂️ Indice:

- [Descrizione](#-descrizione)
- [Tecnologie Utilizzate](#-tecnologie-utilizzate)
- [Installazione e Configurazione](#-installazione-e-configurazione)
- [Struttura Del Progetto](#-struttura-del-progetto)
- [Contatti](#-contatti)
- [Licenza](#-licenza)

---

## 📜 Descrizione

**Bonny Api** nasce dall'idea di visualizzare il tempo risparmiato dagli utenti che utilizzano determinati servizi.
L'applicazione permette di:
    - Gestire le Prestazioni;
    - Gestire le vendite di determinati servizi;
    - Gestire i vari dettagli di ogni vendita;
    - Visualizzare il tempo totale risparmiato aggiungendo filtri come:
        - La data di inizio;
        - La data di fine;
        - L'id della prestazione specifica.
È un servizio RESTful sviluppato in PHP che espone endpoint JSON per la gestione delle prestazioni, delle vendite e il calcolo del tempo risparmiato

---

## 🛠️ Tecnologie Utilizzate

| Tecnologia           | Versione | Descrizione                          |
|----------------------|----------|--------------------------------------|
| **PHP**              | 8.5.10   | Linguaggio di programmazione Backend |
| **MySQL**            | 27.7.0   | Database relazionale                 |
| **Composer**         | 2.10.3   | Gestione delle dipendenze            |
| **vlucas/phpdotenv** | 5.7.0    | Gestione variabili d'ambiente        |

---

## Installazione e configurazione

### 1. Clonare il Repository:

```bash
# Clona il repository
git clone <url-del-repository>

# Entra nella Directory del progetto
cd bonny-api
```

### 2. Installare le dipendenze:

```bash
composer install
```

### 3. Creazione delle variabili d'ambiente

- Duplica il file `.env.example` e rinominalo `.env`;
- Apri il file `.env` appena creato e inserisci le tue credenziali:

```bash
DB_CONNECTION= driver del database, ad esempio mysql.
DB_HOST= indirizzo del server.
DB_PORT= porta MySQL.
DB_NAME= nome del database.
DB_USER= utente.
DB_PASSWORD= password.

```

### 4. Creazione del Database:

Utilizzando il tuo MySQL client preferito, carica il file `migrations.sql`.
Il file crea il database chiamato **bonny_api** e le seguenti tabelle `prestations`, `sales`, `sales_details`.

### 5. Accensione Server

**PHP Built In Server** viene fatto partire, nella cartella progetto, con il seguente comando da terminale: (si utilizza -t per determinare in anticipo la cartella che contiene il file index.php entrypoint)

```bash
php -S localhost:8000 -t public
```

Per questo progetto abbiamo utilizzato come base URL `http://localhost:8000`.

---

##  📂 Struttura del Progetto:

### 1. Albero di costruzione:

```
├── public
│   └── index.php
├── src
│   ├── Config
│   │   └── config.php
│   ├── Controllers
│   │   ├── prestationsController.php
│   │   └── salesController.php
│   ├── Repositories
│   │   ├── PrestationsRepository.php
│   │   └── salesRepository.php
│   ├── Services
│   │   ├── prestationsService.php
│   │   └── salesService.php
│   ├── database
│   │   └── connection.php
│   ├── helpers
│   │   └── helpers.php
│   └── routes
│       ├── prestationsRoutes.php
│       └── salesRoutes.php
├── .env.example
├── .gitignore
├── README.md
├── composer.json
└── migrations.sql
```

Tutti i file sono stati suddivisi per le loro responsabilità, così da avere una struttura più chiara e pulita.

### 2. Routes Disponibili

#### 2.1 Prestazioni:

Ho scelto di non effettuare una vera e propria eliminazione della prestazione ma solo una disattivazione tramite il campo active.
Questo serve per tenere la storicità dei dettagli vendita salvati nel DB, dal caso si effettuasse una cancellazione vera e propria della prestazione, al calcolo del tempo totale risparmiato, ci sarebbero errori di congruenza.
Questo avviene tramite **time_saved_snapshot**:
    - Si effettua una copia in `sales_details` del tempo risparmiato al momento della vendita;
    - Le modifiche successive alla prestazione in merito al tempo, non vanno ad alterare i dati storici nel DB.
Se una dettaglio vendita è stato inserito mesi fa ed oggi il tempo cambia, il dettaglio storico non deve subire alterazioni, ma solo i nuovi dettagli che si andranno ad inserire avranno il minutaggio corrente aggiornato.
Di conseguenza a questa scelta vi è anche una Attivazione della prestazione stessa.

| Metodo | URL                | Descrizione                              |
|--------|--------------------|------------------------------------------|
| GET    | /prestations       | Ritorno di tutte le prestazioni attive   |
| POST   | /prestations       | Creazione nuova prestazione              |
| PUT    | /prestations/{id}  | Aggiornamento nuova prestazione          |
| PATCH  | /prestations/{id}  | Attivazione nuova prestazione            |
| DELETE | /prestations/{id}  | Disattivazione nuova prestazione         | 


**Struttura del body in ingresso con la richiesta POST / PUT**

```json
{
  "name": "Bonus ISEE",
  "time_saved": 45
}
```

#### 2.2 Vendite / Dettagli Vendita: 

| Metodo | URL                                  | Descrizione                                                           |
|--------|--------------------------------------|-----------------------------------------------------------------------|
| GET    | /sales                               | Ritorno di tutte le vendite                                           |
| GET    | /total-time-saved                    | Ritorno del minutaggio totale risparmiato                             |
| GET    | /sales/{id}                          | Ritorno della vendita selezionata con i suoi dettagli                 |
| POST   | /sales                               | Creazione nuova vendita                                               |
| POST   | /sales/{id}/details                  | Inserimento di un nuovo dettaglio all'interno della vendita specifica |
| DELETE | /sales/{id}                          | Cancellazione della vendita selezionata                               | 
| DELETE | /sales/{id_sale}/details/{id_detail} | Cancellazione di un dettaglio da una vendita specifica                |

**Filtraggi disponibili per il tempo totale rispamriato (query string):**
Ci possono essere fino a 8 combinazioni differenti di filtraggi, ogni combinazione ha un messaggio specifico di ritorno in formato json:
    - Nessun filtraggio;
    - Filtraggio per prestazione;
    - Filtraggio per data d'inizio;
    - Filtraggio per data di fine;
    - Filtraggio per prestazione con una data d'inizio;
    - Filtraggio per una prestazione con data di fine;
    - Filtraggio per data d'inizio e fine;
    - Filtraggio per prestazione con data di inizio e fine.

| Parametri  | Esempio           | Descrizione                               |
| ---------- | ----------------- | ----------------------------------------- |
| id_prestation | ?id_prestation=2     | Filtraggio rispetto ad una specifica prestaione   |
| start_date  | ?start_date=2026-10-20   | Filtraggio con una data di inizio specifica        |
| end_date  | ?end_date=2026-10-20   | Filtraggio con una data di fine specifica        |

**Struttura del body in ingresso con la richiesta POST**

```json
[
    {
        "id_prestation": 2,
        "quantity": 45
    }
]
```

### 3 Esempio Risposte

**GET /prestations:**

```json
[
  {
    "name": "Richiesta SPID",
    "time_saved": 15
  },
  {
    "name": "Bonus ISEE",
    "time_saved": 23
  }
]
```

**GET /sales:**

Nella risposta delle vendite vengono mostrati tutti i dettagli in contemporanea, tramite Query SQL le vendite vengono ordinate in modo ascendente per l'ID di vendita.

```json
[
  {
    "id": 1,
    "sale_date": "2026-10-20",
    "name": "Pagamento Bollo Autovettura",
    "quantity": 2
  },
  {
    "id": 3,
    "sale_date": "2026-10-27",
    "name": "Bonus ISEE",
    "quantity": 7
  }
]
```

**GET /total-time-saved:**

Per il tempo risparmiato la risposta è personalizzata in base alla query string:
Per esempio il tempo risparmiato per la prestaizone n.3:

```json
{
  "message": "Il tempo totale risparmiato è di 0 minuti, per la prestazione n. 3"
}
```
### 4. Codici HTTP Status  usati

| Code | Descrizione                                        |
| ---- | -------------------------------------------------- |
| 200  | OK – Risposta effettuata con successo              |
| 201  | Created – Risorsa creata con successo              |
| 404  | Not Found – Risorsa non trovata                    |
| 422  | Unprocessable Entity – Dati invalidi o inesistenti |
| 500  | Internal Server Error – errore lato server         |

## 📩 Contatti

Christian Giaccardi - 📧 [chrigiaccardi@gmail.com](mailto:chrigiaccardi@gmail.com) <br>
GitHub - [chrigiaccardi](https://github.com/chrigiaccardi) <br>
LinkedIn - [LinkedIn](https://it.linkedin.com/in/christian-giaccardi-753085180?trk=public_profile_browsemap_profile-result-card_result-card_full-click)

---

## 📄 Licenza

Questo progetto è open-source e disponibile sotto la licenza **MIT**.