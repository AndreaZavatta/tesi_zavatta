//import {showTrafficData} from "utilities.js";

let map;
let mapLayerGroup;
let carIcon;
let cycleTimer;

const tiles = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
	maxZoom: 19,
	attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
});

carIcon = L.Icon.extend({
	options:{
		iconUrl: "resources/car-front.svg",
		popupAnchor: [-3, -20]
		//className: "markerDirectionAnim"
	}
});

// Initial map setup
function initMap(){
	console.log("initMap: Starting map initialization.");
	if (typeof map !== "undefined") {
		mapLayerGroup.clearLayers();
		console.log("initMap: Map exists. Layers cleared.");
	} else {
		map = L.map('map', {
			center: [44.5, 11.349],
			zoom: 13,
			layers: tiles
		});
		mapLayerGroup = L.layerGroup().addTo(map);
		console.log("initMap: New map created and initialized.");
	}
	console.log("initMap: Map initialization complete.");
}

// Reset the map to its initial state
function resetMap(){
	console.log("resetMap: Resetting the map.");
	if (typeof map !== "undefined") {
		mapLayerGroup.clearLayers();
		console.log("resetMap: Layers cleared.");
	} else {
		console.log("resetMap: No map found. Initializing new map instance.");
		initMap();
	}
	console.log("resetMap: Map reset complete.");
}

/* Display traffic data on the map */
function showTrafficData(data, startHour = 0, endHour = 24, wholeDay = true){
	console.log(`showTrafficData: Displaying traffic data for hours ${startHour} to ${endHour}. Whole day: ${wholeDay}`);
	if (data.length <= 0) {
		console.warn("showTrafficData: No traffic data to show.");
		return;
	}
	console.log("before resetMap");
	resetMap();
	console.log("after resetMap");

	let trafficDictionary = {};
	let spireDictionary = {};
	let streetsTrafficWithDirection = {};
	let dates = [];
	let month_year = [];

	data.forEach((item, index) => {
		console.log(`showTrafficData: Processing item ${index + 1} of ${data.length}`);
		let lat = parseFloat(item["latitudine"]);
		let long = parseFloat(item["longitudine"]);
		let cars = 0;
		console.log('zava endHour: '+endHour)
		for (let i = startHour; i < endHour; i++) {
			let hourKey = ('00' + i).slice(-2) + ":00-" + ('00' + (i + 1)).slice(-2) + ":00";
			console.log('hourkey: '+hourKey);
			let hourCars = item[hourKey];
			cars += hourCars || 0; // Default to 0 if undefined
			console.log(`showTrafficData: Hour ${hourKey}: ${hourCars} cars, Cumulative: ${cars}`);
		}

		if (!dates.includes(item["data"])) {
			dates.push(item["data"]);
			console.log(`showTrafficData: New date added ${item["data"]}`);
		}

		if (item["mese"] && item["anno"] && !month_year.includes(`${item["anno"]}-${item["mese"]}`)) {
			month_year.push(`${item["anno"]}-${item["mese"]}`);
			console.log(`showTrafficData: New month-year added ${item["anno"]}-${item["mese"]}`);
		}

		// Updates to traffic and spire dictionaries
		if (!(item["nome_via"] in trafficDictionary)) {
			trafficDictionary[item["nome_via"]] = {totalCars: cars, geoPoints: [[lat, long]]};
			console.log(`showTrafficData: New street entry ${item["nome_via"]} with ${cars} cars.`);
		} else {
			let existingCars = trafficDictionary[item["nome_via"]]["totalCars"];
			trafficDictionary[item["nome_via"]]["totalCars"] = existingCars + cars;
			trafficDictionary[item["nome_via"]]["geoPoints"].push([lat, long]);
			console.log(`showTrafficData: Updated street entry ${item["nome_via"]}, total cars: ${trafficDictionary[item["nome_via"]]["totalCars"]}`);
		}

		// More detailed data processing for streets and directions
		let key = `${item["nome_via"]} / ${item["direzione"]}`;
		if (!(key in streetsTrafficWithDirection)) {
			streetsTrafficWithDirection[key] = {
				streetName: item["nome_via"], 
				totalCars: cars, 
				geoPoint: [[lat, long]], 
				direction: item["direzione"]
			};
			console.log(`showTrafficData: New street with direction ${key}, cars: ${cars}`);
		} else {
			let existingCars = streetsTrafficWithDirection[key]["totalCars"];
			streetsTrafficWithDirection[key]["totalCars"] = existingCars + cars;
			streetsTrafficWithDirection[key]["geoPoint"].push([lat, long]);
			console.log(`showTrafficData: Updated street with direction ${key}, total cars: ${streetsTrafficWithDirection[key]["totalCars"]}`);
		}
	});

	console.log("showTrafficData: All traffic data processed, now updating the display.");
	showMarkers_icons(streetsTrafficWithDirection, dates.length + (month_year.length * 30));

	if (document.getElementById("heatMap").checked) {
		console.log("showTrafficData: Heatmap is enabled. Displaying heatmap.");
		heatmap_plugin(spireDictionary);
	}
	console.log("showTrafficData: Traffic data display complete.");
}

// Additional debugs can be placed in other functions as needed to track their specific behaviors and data handling.


/*Vado a costruire la mappa con tutte le informazioni in ingresso.*/
function showTrafficData(data, startHour = 0, endHour = 24, wholeDay = true){
	console.log("Showing traffic data:", data, startHour, endHour, wholeDay);
	if (data.length <= 0) {
		console.warn("No traffic data to show.");
		return;
	}
	console.log("before resetMap");
	resetMap();
	console.log("after resetMap");

	let trafficDictionary = {};
	let spireDictionary = {};
	let streetsTrafficWithDirection = {};
	let dates = [];
	let month_year = [];
	if(wholeDay){
		endHour = 24;
	}
	// Processing each traffic item
	data.forEach((item, index) => {
		console.log(`Processing item ${index + 1}/${data.length}`);
		let lat = parseFloat(item["latitudine"]);
		let long = parseFloat(item["longitudine"]);
		console.log('lat '+lat);
		console.log('long '+long)
		let cars = 0;
		console.log('startHour '+startHour);
		console.log('endHour '+endHour);


		// Sum cars for the relevant hours
		for (let i = startHour; i < endHour; i++) {
			let hourKey = ('00' + i).slice(-2) + ":00-" + ('00' + (i + 1)).slice(-2) + ":00";
			let hourCars = item[hourKey];
			cars += hourCars || 0; // Add a default 0 in case of missing data
			console.log(`Hour ${hourKey}: ${hourCars} cars, Total: ${cars}`);
		}

		if (!dates.includes(item["data"])) {
			dates.push(item["data"]);
			console.log("Added new date:", item["data"]);
		}

		if (item["mese"] && item["anno"] && !month_year.includes(`${item["anno"]}-${item["mese"]}`)) {
			month_year.push(`${item["anno"]}-${item["mese"]}`);
			console.log("Added new month-year:", `${item["anno"]}-${item["mese"]}`);
		}

		// Handling traffic dictionary
		if (!(item["nome_via"] in trafficDictionary)) {
			trafficDictionary[item["nome_via"]] = {totalCars: cars, geoPoints: [[lat, long]]};
			console.log(`New street: ${item["nome_via"]} with ${cars} cars.`);
		} else {
			let existingCars = trafficDictionary[item["nome_via"]]["totalCars"];
			trafficDictionary[item["nome_via"]]["totalCars"] = existingCars + cars;
			trafficDictionary[item["nome_via"]]["geoPoints"].push([lat, long]);
			console.log(`Updated street: ${item["nome_via"]}, total cars: ${trafficDictionary[item["nome_via"]]["totalCars"]}`);
		}

		// Handling spire dictionary
		if (!(item["codice_spira"] in spireDictionary)) {
			spireDictionary[item["codice_spira"]] = {
				totalCars: cars, 
				geoPoint: [lat, long], 
				date: [item["data"]], 
				streetName: item["nome_via"]
			};
			console.log(`New spire: ${item["codice_spira"]}, cars: ${cars}`);
		} else {
			let existingCars = spireDictionary[item["codice_spira"]]["totalCars"];
			spireDictionary[item["codice_spira"]]["totalCars"] = existingCars + cars;
			spireDictionary[item["codice_spira"]]["date"].push(item["data"]);
			console.log(`Updated spire: ${item["codice_spira"]}, total cars: ${spireDictionary[item["codice_spira"]]["totalCars"]}`);
		}

		let key = `${item["nome_via"]} / ${item["direzione"]}`;
		if (!(key in streetsTrafficWithDirection)) {
			streetsTrafficWithDirection[key] = {
				streetName: item["nome_via"], 
				totalCars: cars, 
				geoPoint: [[lat, long]], 
				direction: item["direzione"]
			};
			console.log(`New street with direction: ${key}, cars: ${cars}`);
		} else {
			let existingCars = streetsTrafficWithDirection[key]["totalCars"];
			streetsTrafficWithDirection[key]["totalCars"] = existingCars + cars;
			streetsTrafficWithDirection[key]["geoPoint"].push([lat, long]);
			console.log(`Updated street with direction: ${key}, total cars: ${streetsTrafficWithDirection[key]["totalCars"]}`);
		}
	});

	console.log("Processed all traffic data. Now displaying markers.");
	showMarkers_icons(streetsTrafficWithDirection, dates.length + (month_year.length * 30));

	if (document.getElementById("heatMap").checked) {
		console.log("ciaociao")
		console.log("Heatmap is enabled. Showing heatmap.");
		console.log("spire Dictionary: "+(spireDictionary));
		heatmap_plugin(spireDictionary);
	}
	console.log("Finished displaying traffic data.");
}

/*Disegna sulla mappa tutti i segnalini delle spire, con o senza animazioni*/
function showMarkers_icons(streetsTrafficWithDirection, ndays = 1) {
	let maxCars = getMax_spire(streetsTrafficWithDirection);
	console.log("Max cars for any spire:", maxCars);

	Object.entries(streetsTrafficWithDirection).forEach(([key, value]) => {
		console.log(`Drawing marker for: ${key}, cars: ${value["totalCars"]}`);
		if (value["direction"].length > 0 && value["totalCars"] > 0) {
			let size = Math.max(8, 50 * (value["totalCars"] / maxCars));  // Minimum size of 8
			if (value["geoPoint"].length <= 1 || !document.getElementById("animatedMarkers").checked) {
				let marker = L.marker([value["geoPoint"][0][0], value["geoPoint"][0][1]], {icon: new carIcon({iconSize: [size, size]})}).addTo(mapLayerGroup);
				marker.bindPopup(`Nome via: ${value["streetName"]}<br>Direzione: ${value["direction"]}<br>Veicoli transitati: ${value["totalCars"]}<br>Media veicoli giornaliera: ${Math.floor(value["totalCars"] / ndays)}`);
			} else {
				let pointList = value["geoPoint"].map(coord => ({
					latlng: L.latLng(coord[0], coord[1])
				}));

				console.log(pointList);
				let markerPlayer = L.markerPlayer(pointList, 5000, {icon: new carIcon({iconSize: [size, size]}), loop: true, autostart: true}).addTo(mapLayerGroup);
				markerPlayer.bindPopup(`Nome via: ${value["streetName"]}<br>Direzione: ${value["direction"]}<br>Veicoli transitati: ${value["totalCars"]}<br>Media veicoli giornaliera: ${Math.floor(value["totalCars"] / ndays)}`);
			}
		}
	});
}

/* Cerca il massimo in un dictionary in cui nel suo campo valore ha il campo "totalCars" */
function getMax_spire(spireDictionary){
	let max;
	for ([key, value] of Object.entries(spireDictionary)){
		max = typeof max === "undefined" ? value["totalCars"] : Math.max(max, value["totalCars"]);
	}
	console.log("Max spire traffic found:", max);
	return max;
}

/*Disegna sulla mappa la strada più trafficata*/
function showBusiestRoad(trafficDictionary){
	let maxTraffic;
	let maxTrafficLocation;
	for ([key, value] of Object.entries(trafficDictionary)){
		if(typeof maxTraffic === "undefined" || (maxTraffic["totalCars"] / maxTraffic["geoPoints"].length) < (value["totalCars"] / value["geoPoints"].length)){
			maxTraffic = value;
		}
	}
	if(maxTraffic["geoPoints"].length <= 1){
		maxTrafficLocation = L.circle(maxTraffic["geoPoints"][0], {radius: 200, color: "red"}).addTo(mapLayerGroup);
	} else {
		maxTrafficLocation = L.polyline(maxTraffic["geoPoints"], {color: "red"}).addTo(mapLayerGroup);
	}
	maxTrafficLocation.bindPopup("Tratto maggiormente trafficato");
}

/*Disegna sulla mappa le zone più o meno trafficate */
function showHeatMap(spireDictionary, zones = 1){
	let maxLat, maxLong, minLat, minLong;
	let drawGrid = false;
	new Promise(resolve => {
		for ([key, value] of Object.entries(spireDictionary)){
			let itemLat = value["geoPoint"][0];
			let itemLong = value["geoPoint"][1];
			if(typeof maxLat === "undefined"){
				maxLat = itemLat;
				minLat = itemLat;
				maxLong = itemLong;
				minLong = itemLong;
				continue;
			}
			minLat = Math.min(itemLat, minLat);
			maxLat = Math.max(itemLat, maxLat);
			minLong = Math.min(itemLong, minLong);
			maxLong = Math.max(itemLong, maxLong);
		}
		resolve();
	}).then(function(){
		let latOffset = maxLat / (1500 * (parseInt($("#heatMapZonesRange").attr("max")) + 1 - parseInt(document.getElementById("heatMapZonesRange").value)));
		let longOffset = maxLong / (300 * ( parseInt($("#heatMapZonesRange").attr("max")) + 1 - parseInt(document.getElementById("heatMapZonesRange").value)));

		new Promise(resolve => {
			if(drawGrid){
				L.polyline([[maxLat, maxLong], [minLat, maxLong], [minLat, minLong], [maxLat, minLong], [maxLat, maxLong]], {color: "blue"}).addTo(mapLayerGroup);
				for(let i = minLat; i <= maxLat; i += latOffset){
					L.polyline([[i, minLong], [i, maxLong]], {color: "blue"}).addTo(mapLayerGroup);
				}
				for(let i = minLong; i <= maxLong; i += longOffset){
					L.polyline([[minLat, i], [maxLat, i]], {color: "blue"}).addTo(mapLayerGroup);
				}
			}
			resolve();
		}).then(function(){
			let areas = {};
			for ([key, value] of Object.entries(spireDictionary)){
				let rectCoords = [Math.floor((parseFloat(value["geoPoint"][0]) - minLat) / latOffset), Math.floor((parseFloat(value["geoPoint"][1]) - minLong) / longOffset)];
				if(!(rectCoords in areas)){
					areas[rectCoords] = {totalCars: value["totalCars"], spires: 1};
				} else {
					areas[rectCoords]["totalCars"] += value["totalCars"];
					areas[rectCoords]["spires"]++;
				}
			}
			let sortedAreas = Object.entries(areas).sort((a, b) => (b[1]["totalCars"] / b[1]["spires"]) - (a[1]["totalCars"] / a[1]["spires"])).filter(item => item[1]["totalCars"] > 0);
			zones = Math.min(Math.max(parseInt(zones), 1), 20);

			for(let i = 0; i < zones; i++){
				let marker = L.rectangle([[parseInt(sortedAreas[i][0].split(",")[0]) * latOffset + minLat, parseInt(sortedAreas[i][0].split(",")[1]) * longOffset + minLong], [(parseInt(sortedAreas[i][0].split(",")[0]) + 1) * latOffset + minLat , (parseInt(sortedAreas[i][0].split(",")[1]) + 1) * longOffset + minLong]], {color: "red"}).addTo(mapLayerGroup);
				marker.bindPopup("Zona molto trafficata");	
			}
			for(let i = sortedAreas.length - 1; i >= sortedAreas.length - zones; i--){
				let marker = L.rectangle([[parseInt(sortedAreas[i][0].split(",")[0]) * latOffset + minLat, parseInt(sortedAreas[i][0].split(",")[1]) * longOffset + minLong], [(parseInt(sortedAreas[i][0].split(",")[0]) + 1) * latOffset + minLat , (parseInt(sortedAreas[i][0].split(",")[1]) + 1) * longOffset + minLong]], {color: "#19e53e"}).addTo(mapLayerGroup);
				marker.bindPopup("Zona poco trafficata");	
			}
		});
	});
}

/*Mostra la mappa di calore*/
function heatmap_plugin(spireDictionary){
	traffic = [];
	let maxCars = getMax_spire(spireDictionary) * 0.7;
	for([key, value] of Object.entries(spireDictionary)){
		console.log('zava value'+ value["totalCars"])
		if(value["totalCars"] > 0){
			traffic.push({lat: value["geoPoint"][0], lng: value["geoPoint"][1], value: value["totalCars"]});
		}
	}
	const config = {
		"maxOpacity": .7,
		"useLocalExtrema": false,
		"radius": 0.004,
		"scaleRadius": true,
		valueField: "value",
		latField: "lat",
		lngField: "lng"
	};

	if (typeof HeatmapOverlay === 'undefined') {
		console.error("HeatmapOverlay is not defined. Ensure leaflet-heatmap.js and heatmap.js are included.");
		return;
	}
	
	let heatmapLayer = new HeatmapOverlay(config);
	mapLayerGroup.addLayer(heatmapLayer);
	heatmapLayer.setData({data: traffic, max: maxCars});
}

/*Rotazione automatica dei giorni*/
function cycleDays(data, startHour = 0, endHour = 24, wholeDay = true){
	let months = ["Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno", "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre"];
	if(typeof cycleTimer !== "undefined"){
		clearInterval(cycleTimer);
	}
	if(data.length <= 0){
		console.log("Non è arrivato nessun dato");
		return;
	}
	if(typeof data[0]["data"] !== "undefined"){
		data.sort((a, b) => (new Date(a["data"]) - new Date(b["data"])));
		let startDate = new Date(data[0]["data"]);
		let endDate = new Date(data[data.length - 1]["data"]);
		startDate.setHours(0, 0, 0);
		endDate.setHours(0, 0, 0);
		let curDate = new Date(startDate);
		let dateInterval = new Date(curDate);
		const cd = setInterval(function(){
			if(document.getElementById("cyclingDays").checked && !document.getElementById("singleDay").checked && (document.getElementById("rotationType").value === "day" || document.getElementById("rotationType").value === "week" )){
				if(curDate > endDate){
					curDate = new Date(startDate);
				}
				dateInterval = structuredClone(curDate);
				switch(document.getElementById("rotationType").value){
					case "week":
						curDate = addDays(curDate, 7);
						break;
					default:
						curDate = addDays(curDate, 1);
						break;
				}
				let curData = data.filter((item) => new Date(item["data"]) >= dateInterval && new Date(item["data"]) < curDate);
				if(document.getElementById("rotationType").value === "day"){
					document.getElementById("mapTitle").innerHTML = "Dati del " + (dateInterval.getDate()) + "/" + (dateInterval.getMonth() + 1) + "/" + dateInterval.getFullYear() + " (" + startHour + ":00 - " + endHour + ":00)";
				} else if (document.getElementById("rotationType").value === "week") {
					let noLastDay = addDays(curDate, -1);
					document.getElementById("mapTitle").innerHTML = "Dati dal " + dateInterval.getDate() + "/" + (dateInterval.getMonth() + 1) + "/" + dateInterval.getFullYear() + " al " + (noLastDay.getDate()) + "/" + (noLastDay.getMonth() + 1) + "/" + noLastDay.getFullYear() + " (" + startHour + ":00 - " + endHour + ":00)";
				}
				showTrafficData(curData, startHour, endHour, wholeDay);
			} else {
				document.getElementById("mapTitle").innerHTML = "";
				clearInterval(cd);
			}
		}, 4000);
		cycleTimer = cd;	
	}
	else if(typeof data[0]["mese"] !== "undefined"){
		let startMonth = data[0]["mese"];
		let startYear = data[0]["anno"];
		let endMonth = data[data.length - 1]["mese"];
		let endYear = data[data.length - 1]["anno"];
		let curMonth = startMonth;
		let curYear = startYear;
		const cd = setInterval(function(){
			if(document.getElementById("cyclingDays").checked && !document.getElementById("singleDay").checked && document.getElementById("rotationType").value === "month"){
				if(curYear > endYear || (curYear == endYear && curMonth > endMonth)){
					curMonth = startMonth;
					curYear = startYear;
				}
				let curData = data.filter((item) => parseInt(item["mese"]) === parseInt(curMonth) && item["anno"] === curYear);
				showTrafficData(curData, startHour, endHour, wholeDay);
				document.getElementById("mapTitle").innerHTML = "Dati di " + months[curMonth] + " " + curYear;
				curMonth++;
				if(curMonth > 12){
					curMonth = 1;
					curYear++;
				}
			} else {
				document.getElementById("mapTitle").innerHTML = "";
				clearInterval(cd);
			}
		}, 4000);
		cycleTimer = cd;	
	}
	return;
}

function addDays(date, days){
	date = new Date(date);
	date.setDate(date.getDate() + days);
	date.setHours(0, 0, 0);
	return date;
}

function addMonth(date) {
	date = new Date(date);
    let d = date.getDate();
    date.setMonth(date.getMonth() + 1);
    if (date.getDate() != d) {
      date.setDate(0);
    }
    return date;
}