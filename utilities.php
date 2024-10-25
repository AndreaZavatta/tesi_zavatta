<?php

function stylesheets(){
    return '
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" integrity="sha256-kLaT2GOSpHechhsozzB+flnD+zUyjE2LlfWPgU04xyI=" crossorigin=""/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous"/>
    <link rel="stylesheet" href="./css/main.css"/>
    ';
}

function scripts(){
  return '
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
  <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
  <script type="module" src="./js/utilities.js"></script>
  <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js" integrity="sha256-WBkoXOwTeyKclOHuWtc+i2uENFpDZ9YPdf5Hf+D7ewM=" crossorigin=""></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
  <script src="./js/map.js"></script>
  <script type="module" src="./js/controls.js"></script>
  <script src="./node_modules/heatmap.js/build/heatmap.js"></script>
  <script src="./js/leaflet-heatmap.js"></script>
  <script src="https://unpkg.com/leaflet.markerplayer"></script>
  ';
}


/**/
function get_traffic_data($startDate, $endDate, $startHour = 0, $endHour = 24, $rotation = false, $rotationType = ""){
  
  //Dates are YYYY-MM-DD
  $time_start = microtime(true);
  $startDate = date_create($startDate, timezone_open("Europe/Rome"));
  $connection = connection();
  $endDate = date_create($endDate, timezone_open("Europe/Rome"));
  //echo $endDate->format("Y-m-d");
  if ($startDate > $endDate){
    return "";
  }
  //curl_setopt($curl, CURLOPT_HTTPGET, 1); //GET is default
  $result_rows = array();
  $api_formatted_startDate = date_format($startDate, "Y-m-d");
  $api_formatted_endDate = date_format($endDate, "Y-m-d");
  //L'ottimizzazione viene eseguita quando non si devono mostrare i giorni a rotazione ogni n secondi, in quanto non servono le date precise dei dati, ma solo una loro aggregazione.
  //Quando invece si vuole far vedere i giorni/settimane a rotazione, serve sapere le date precise dei dati in quanto essi devono essere visualizzati per periodi differenti (es. 23 giugno -> 23 luglio), per cui non è possibile aggregarli preventivamente
  $startMonth = clone($startDate);
  date_add($startMonth, date_interval_create_from_date_string("1 month"));
  $startYear = explode("-", $startMonth->format("Y-m-d"))[0];
  $startMonth = explode("-", $startMonth->format("Y-m-d"))[1];
  $endMonth = clone($endDate);
  date_sub($endMonth, date_interval_create_from_date_string("1 month"));
  $endYear = explode("-", $endMonth->format("Y-m-d"))[0];
  $endMonth = explode("-", $endMonth->format("Y-m-d"))[1];
  $diff = date_diff($startDate, $endDate);
  $init_query = 'SELECT *
                  FROM comuni
                  INNER JOIN vie ON comuni.id = vie.comune_id
                  INNER JOIN spire ON vie.id = spire.codice_via
                  INNER JOIN rilevazioni_traffico ON spire.id = rilevazioni_traffico.codice_spira
                  INNER JOIN dettagli_traffico ON rilevazioni_traffico.id = dettagli_traffico.id_rilevazione
                  INNER JOIN dettagli_generali ON rilevazioni_traffico.id = dettagli_generali.id_rilevazione';
  if($rotation == false || $rotation == "false"){
    //echo $endDate->format("Y-m-d");
    // ------------------ Reperimento dati mensili aggregati --------------------
    // Ho almeno un mese pieno
    //echo $diff->format("%m") + 12 * $diff->format("%y");
    if($diff->format("%m") + 12 * $diff->format("%y") > 1){
      $query = "";
      //echo $startYear . " - ". $endYear;
      if($startYear != $endYear){
        $query = $connection->prepare($init_query." WHERE mese BETWEEN ? AND 12 AND anno = ? OR mese BETWEEN 1 AND ? AND anno = ? OR mese BETWEEN 1 AND 12 AND anno > ? AND anno < ?");
        //echo "SELECT * FROM `rilevazione-flusso-veicoli-tramite-spire-dati-mensili` WHERE mese BETWEEN " . $startMonth . " AND 12 AND anno = " . $startYear . " OR mese BETWEEN 1 AND " . $endMonth . " AND anno = " . $endYear . " OR mese BETWEEN 1 AND 12 AND anno > " . $startYear . " AND anno < " . $endYear;
        $query->bind_param("iiiiii", $startMonth, $startYear, $endMonth, $endYear, $startYear, $endYear);
        $query->execute();  
      }
      else{
        //echo "ciao";
        $query = $connection->prepare($init_query." WHERE mese BETWEEN ? AND ? AND anno = ?");
        $query->bind_param("iii", $startMonth, $endMonth, $startYear);
      }
      //echo "SELECT * FROM `rilevazione-flusso-veicoli-tramite-spire-dati-mensili` WHERE mese BETWEEN " . $startMonth . " AND " . $endMonth . " AND anno BETWEEN " . $startYear . " AND " . $endYear;
      $query->execute();
      $result = $query->get_result();
      while($row = $result->fetch_assoc()){
        $result_rows[] = $row;
      }
    }
    // -------------- Reperimento dati giornalieri rimanenti -------------------
    //Se il mese ed anno di fine ed inizio sono gli stessi
    if(explode("-", $api_formatted_startDate)[1] == explode("-", $api_formatted_endDate)[1] && explode("-", $api_formatted_startDate)[0] == explode("-", $api_formatted_endDate)[0]){
      //echo "stesso mese e stesso anno";
      $query = $connection->prepare($init_query." WHERE data BETWEEN ? AND ?");
      $query->bind_param("ss", $api_formatted_startDate, $api_formatted_endDate);
      $query->execute();
      $result = $query->get_result();
      while($row = $result->fetch_assoc()){
        //echo $row["data"] . "\n";
        $result_rows[] = $row;
      }  
    }
    else{
      $endOfStartingMonth = date("Y-m-t", strtotime($api_formatted_startDate));
      $startOfEndMonth = date("Y-m-01", strtotime($api_formatted_endDate));
      //echo "SELECT * FROM `rilevazione-flusso-veicoli-tramite-spire-anno-2022` WHERE data BETWEEN " . $api_formatted_startDate . " AND ". $endOfStartingMonth . " AND data BETWEEN " . $startOfEndMonth . " AND ". $api_formatted_endDate;
      // SELECT * FROM `rilevazione-flusso-veicoli-tramite-spire-anno-2022` WHERE data BETWEEN 2021-10-30 AND 2021-10-31 AND data BETWEEN 2022-11-01 AND 2022-11-23 
      $query = $connection->prepare($init_query." WHERE data BETWEEN ? AND ? OR data BETWEEN ? AND ?");
      $query->bind_param("ssss", $api_formatted_startDate, $endOfStartingMonth, $startOfEndMonth, $api_formatted_endDate);
      $query->execute();
      $result = $query->get_result();
      while($row = $result->fetch_assoc()){
        $result_rows[] = $row;
      }
    }
  }
  else{
    switch($rotationType){
      case "day":
      case "week":
        if($diff->format("%a") > 60){
          return;
        }
        $query = $connection->prepare($init_query." WHERE data BETWEEN ? AND ?");
        $query->bind_param("ss", $api_formatted_startDate, $api_formatted_endDate);
        $query->execute();
        $result = $query->get_result();
        while($row = $result->fetch_assoc()){
          //echo $row["data"] . "\n";
          $result_rows[] = $row;
        }    
        break;
        case "month":
          $startMonth = clone($startDate);
          $startYear = explode("-", $startMonth->format("Y-m-d"))[0];
          $startMonth = explode("-", $startMonth->format("Y-m-d"))[1] - 1;
          $endMonth = clone($endDate);
          $endYear = explode("-", $endMonth->format("Y-m-d"))[0];
          $endMonth = explode("-", $endMonth->format("Y-m-d"))[1] - 1;        
          $query = $connection->prepare($init_query." WHERE mese BETWEEN ? AND ? AND anno BETWEEN ? AND ?");
          $query->bind_param("iiii", $startMonth, $endMonth, $startYear, $endYear);
          $query->execute();
          $result = $query->get_result();
          while($row = $result->fetch_assoc()){
            $result_rows[] = $row;
          }    
          break;
    }
  }
  /*$endOfStartingMonth = date("Y-m-t", $api_formatted_startDate);
  $startOfEndMonth = date("Y-m-01", $api_formatted_endDate);
  $connection->prepare("SELECT * FROM `rilevazione-flusso-veicoli-tramite-spire-anno-2022` WHERE data BETWEEN ? AND ? AND data BETWEEN ? AND ?");
  $query->bind_param("ssss", $api_formatted_startDate, $endOfStartingMonth, $startOfEndMonth, $api_formatted_endDate);
  $query->execute();
  $result = $query->get_result();
  while($row = $result->fetch_assoc()){
    //echo $row["data"] . "\n";
    $result_rows[] = $row;
  }*/
  // ----------------------- Fine reperimento dati --------------------------

  /*$query = $connection->prepare("SELECT * FROM `rilevazione-flusso-veicoli-tramite-spire-anno-2022` WHERE data BETWEEN ? AND ?");
  $query->bind_param("ss", $api_formatted_startDate, $api_formatted_endDate);
  $query->execute();
  $result = $query->get_result();
  while($row = $result->fetch_assoc()){
    //echo $row["data"] . "\n";
    $result_rows[] = $row;
  }*/
  $json_result = json_encode($result_rows);
  $time_end = microtime(true);
  //echo "Dati in DB reperiti in " . floor(($time_end - $time_start) * 100) / 100 . " secondi";
  return $json_result;
}



function connection(){
  $connection = new mysqli("127.0.0.1:3306", "root", "ErZava01", "prova");
  if($connection->connect_error){
    die('Error connecting to the database');
  }
  return $connection;
}

function show_traffic_data($startDate, $endDate, $startHour = 0, $endHour = 24){
  $traffic_info_json = get_traffic_data($startDate, $endDate, $startHour, $endHour);
  if(strlen($traffic_info_json) <= 0){
    echo "<script>resetMap();</script>";
    return;
  }
  echo "<script>showTrafficData(" . $traffic_info_json . ", " . $startHour . ", " . $endHour . ");</script>";
}

?>