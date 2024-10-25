<!DOCTYPE html>
  
<?php

require "utilities.php";
$months =  ["Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno", "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre"];
$years = [2019, 2020, 2021];

?>
<html>

	<head>
		<?php echo stylesheets(); ?>
		<title> Map </title>
	</head>

  	<body>
		<div class="container" id="app">
			<fieldset>
				<legend> Controlli </legend>
				<h3>
					<div v-html="alerts">
						{{alerts}}
					</div>
				</h3>
				<div id="timer">
				</div>
				<div>
					<label for="startDay">Giorno iniziale di cui visualizzare le informazioni:</label>
					<input type="date" id="startDay" name="startDay" :onchange="startDayChange" value="2022-10-30">
				</div>
				
				<div id="endDayDiv">
					<label for="endDay" id="endDayLabel">Giorno finale di cui visualizzare le informazioni:</label>
					<input type="date" id="endDay" name="endDay" :onchange="checkAlerts" :disabled="singleDay" value="2022-10-30">
				</div>
				<div id="timeDiv" class="hide">
					<div>
						<label for="startTime">Inserire l'ora di inizio transito:</label>
						<input type="time" id="startTime" name="startTime" :onchange="checkAlerts" :disabled="wholeDay" value="00:00">
					</div>
					<div>
						<label for="endTime" id="endTimeLabel">Inserire l'ora di fine transito:</label>
						<input type="time" id="endTime" name="endTime" :onchange="checkAlerts" :disabled="wholeDay" value="00:00">
					</div>
				</div>
				<div>
					<input type="checkbox" id="entireDay" @click="disableEndTime" :onchange="checkAlerts" :checked="wholeDay"/>
					<label for="entireDay"> Giorno intero </label>
				</div>
				<div>
					<input type="checkbox" id="singleDay" @click="disableEndDay" :checked="singleDay"/>
					<label for="singleDay"> Giorno singolo </label>
				</div>
				<div id="cyclingDaysDiv" class="hide">
					<div>
						<input type="checkbox" id="cyclingDays" @click="toggleCyclingDays" :checked="cyclingDays" :onchange="checkAlerts"/>
						<label for="cyclingDays"> Rotazione giorni </label>
					</div>
					<div id="rotationTypeDiv" class="hide">
						<div>
							<label for="rotationType">Tipo rotazione: </label>
							<select id="rotationType" :onchange="checkAlerts">
								<option value="day"> Giorno </option>
								<option value="week"> Settimana </option>
								<option value="month"> Mese </option>
								<!--option value="year"> Anno </option-->
							</select>
						</div>
					</div>
				</div>
				<div>
					<input type="checkbox" id="heatMap" :checked="heatMap" @click="showHeatMap"/>
					<label for="heatMap"> Mostra mappa di calore </label>
				</div>
				<div>
					<input type="checkbox" id="animatedMarkers" @click="toggleAnimatedMarkers"/>
					<label id="animatedMarkersLabel" for="animatedMarkers"> Animazioni segnalini </label>
				</div>
				<!--div>
					<label id="heatMapZonesRangeLabel" for="heatMapZonesRange"> Dimensione dell'area da considerare: </label>
					<input type="range" id="heatMapZonesRange" name="heatMapZonesRange" value="3" min="1" max="10">
				</div-->

			</fieldset>
			<h2 id="mapTitle"></h2>
			<div id="map" name="map">
			</div>
		</div>
	</body>
	<?php echo scripts(); ?>
<?php 
show_traffic_data("2022-10-30", "2022-10-30");
?>

</html>
