/* In questo file migrations.sql inseriamo le query SQL per la creazione del DB principale
delle tabelle con le loro colonne e proprietà.
Questo rende la clonazione e riproducibilità più efficente */

// Iniziamo creando il DB bonny-api, se il DB esiste già non viene creato
CREATE DATABASE IF NOT EXISTS bonny-api;

/* Con USE dichiariamo a SQL che d'ora in poi utilizzi e scriva sopra questo database*/
USE bonny-api;

/* Creazione delle tabelle: anche le tabelle se esistono già non vengono più create.
Ci sono 3 tabelle:
    1. Prestations -> elenco delle prestazioni;
    2. Sales -> elenco delle vendite;
    3. sales_details -> elenco dei dettagli delle vendite. */
    
/* Tabella 1 Prestations:*/