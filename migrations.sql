/* In questo file migrations.sql inseriamo le query SQL per la creazione del DB principale
delle tabelle con le loro colonne e proprietà.
Questo rende la clonazione e riproducibilità più efficente */

/* Iniziamo creando il DB bonny-api, se il DB esiste già non viene creato */
CREATE DATABASE IF NOT EXISTS bonny_api;

/* Con USE dichiariamo a SQL che d'ora in poi utilizzi e scriva sopra questo database*/
USE bonny_api;

/* Creazione delle tabelle: anche le tabelle se esistono già non vengono più create.
Ci sono 3 tabelle:
    1. Prestations -> elenco delle prestazioni;
    2. Sales -> elenco delle vendite;
    3. sales_details -> elenco dei dettagli delle vendite. */
    
/* Tabella 1 Prestations, si compone di 4 colonne:
    1. id: che è automatico con l'autoincrement ogni qualvolta viene inserito un record,
        Primary key per identificare ogni riga univoca;
    2. name: il nome della prestazione è un varchar di 50 caratteri, not null e 
        unique per essere differente dagli altri. (Non possono esserci due name uguali);
    3. time_saved: Il tempo rispamiato è un INT (integer - numero intero),
        not null e con un check (time_saved >= 0) per garantire che deve essere uguale
        o superiore a 0;
    4. active: active è un booleano true/false per attivare o disattivare la prestazione,
        questa decisione viene dal fatto di manterene storicità nelle elenco delle vendite.
        Se una tipologia venisse cancellata allora anche le vendite collegate di conseguenze,
        invece solamente disattivandola e non facendola vedere al client può comunque lavorare in backgournd. */
CREATE TABLE IF NOT EXISTS prestations(
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    time_saved INT NOT NULL CHECK(time_saved >= 0),
    active BOOLEAN NOT NULL DEFAULT TRUE,

    PRIMARY KEY(id)
);

/* Tabella 2 Sales, si compone du due colonne:
    1. id: che è automatico con l'autoincrement ogni qualvolta viene inserito un record,
        Primary key per identificare ogni riga univoca;
    2. sales_date: la data della vendita. */
CREATE TABLE IF NOT EXISTS sales(
    id INT NOT NULL AUTO_INCREMENT,
    sale_date DATE NOT NULL,

    PRIMARY KEY(id)
);

/* Tabella 3 sales_details, si compone di 5 colonne:
    1. id: che è automatico con l'autoincrement ogni qualvolta viene inserito un record,
        Primary key per identificare ogni riga univoca;
    2. id_sale: con la foreignKey colleghiamo le tabelle tramite l'id, questo id sarà
        quello della tabella sales;
    3. id_prestation: con la foreignKey colleghiamo le tabelle tramite l'id, questo id sarà
        quello della tabella prestations;
    4. quantity: india la quantità di prestazioni eseguite, per esempio "spid" x 1 e "ISEE" x 3;
    5. time_saved_snapshot: questo valore indica il tempo risparmiato al momento dell'inserimento
        delle prestazioni vendute. Potrebbe succedere che le prestazioni nel tempo cambino valori
        del tempo risparmiato ma noi, nel calcolo totale non vogliamo variare le vendite già
        eseguite, quindi questo valore prende riferimento dal time_saved alla creazione della
        vendita ma non alle modifiche successive. */
CREATE TABLE IF NOT EXISTS sales_details(
    id INT NOT NULL AUTO_INCREMENT,
    id_sale INT NOT NULL,
    id_prestation INT NOT NULL,
    quantity INT NOT NULL CHECK(quantity >= 1),
    time_saved_snapshot INT NOT NULL CHECK(time_saved_snapshot >= 0),

    FOREIGN KEY (id_sale) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (id_prestation) REFERENCES prestations(id),
    UNIQUE (id_sale, id_prestation),
    PRIMARY KEY (id)
);

