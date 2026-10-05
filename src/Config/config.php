<?php
// Importiamo la Classe Dotenv
use Dotenv\Dotenv;

/* Creiamo il collegamento e la lettura del file .env utilizzando la classe Dotenv;
dirname va a salire di un livello della directory, quindi due volte arriva alla root del progetto
*/
$dotEnv = Dotenv::createImmutable(dirname(dirname(__DIR__)));

// Carichiamo il file .env per darci la possibilità di leggerlo
$dotEnv->load();


?>