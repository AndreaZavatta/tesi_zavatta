<?php
require "./utilities.php";

$connection = connection();
$mesi = ["Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno", "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre"];
$query = "";
$somma_per_orari = "";
$orari_tabella = "";
for($ora = 0; $ora < 24; $ora++){
    $somma_per_orari .= "sum(`" . sprintf('%02d', $ora) . ":00-" . sprintf('%02d', ($ora + 1)) . ":00`) AS `" . sprintf('%02d', $ora) . ":00-" . sprintf('%02d', ($ora + 1)) . ":00`, ";
    $orari_tabella .= "`" . sprintf('%02d', $ora) . ":00-" . sprintf('%02d', ($ora + 1)) . ":00`, "; 
}


$somma_per_orari = rtrim($somma_per_orari, ", ");
$orari_tabella = rtrim($orari_tabella, ",");

$query = $connection->prepare("DROP TABLE `dati_mensili`");
$query->execute();
/*$query = $connection->prepare("TRUNCATE TABLE `rilevazione-flusso-veicoli-tramite-spire-dati-mensili`");
$query->execute();*/


for($anno = 2023; $anno < 2024; $anno++){
    
    for($mese = 1; $mese <= 12; $mese++){
        //echo "SELECT codice_spira, " . $somma_per_orari . ", `codice via`, nome_via, direzione, longitudine, latitudine, geopoint FROM `rilevazione-flusso-veicoli-tramite-spire-anno-2022_rebuild` WHERE data LIKE \"" . $anno . "-" . sprintf('%02d', $mese) . "-%\" GROUP BY codice_spira, `codice via`, nome_via, direzione, longitudine, latitudine, geopoint";
        //break;
        
        $query = $connection->prepare("CREATE TABLE `dati_mensili` AS SELECT codice_spira, " . $somma_per_orari . ", `codice via`, nome_via, direzione, longitudine, latitudine, geopoint FROM `rilevazione_flusso_veicoli` WHERE data LIKE \"" . $anno . "-" . sprintf('%02d', $mese) . "-%\" GROUP BY codice_spira, `codice via`, nome_via, direzione, longitudine, latitudine, geopoint");
        $query->execute();
        $query = $connection->prepare("ALTER TABLE `dati_mensili` ADD `mese` VARCHAR(20) DEFAULT \"" . $mese . "\" FIRST, ADD `anno` INT DEFAULT " . $anno . " AFTER `mese`");
        $query->execute();
        /*$query = $connection->prepare("ALTER TABLE `dati_mensili` ADD PRIMARY KEY (`mese,`, `anno`, `codice_spira`)");
        $query->execute();*/
        $query = $connection->prepare("INSERT INTO `rilevazione_flusso_veicoli` SELECT * FROM `dati_mensili` ON DUPLICATE KEY UPDATE `latitudine`=VALUES(`latitudine`), `longitudine`=VALUES(`longitudine`), geopoint=VALUES(`geopoint`)");
        $query->execute();
        $query = $connection->prepare("DROP TABLE `dati_mensili`");
        $query->execute();
    }
}

//echo $somma_per_orari;
?>
