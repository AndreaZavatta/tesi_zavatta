import { createApp } from 'https://unpkg.com/vue@3/dist/vue.esm-browser.js';

createApp({
  data() {
	return {
	  alerts: '',
	  wholeDay: true,
	  singleDay: true,
	  heatMap: true,
	  animatedMarkers: false,
	  cyclingDays: false
	}
  },
  methods: {
	  disableEndTime(){
		  this.wholeDay = !this.wholeDay;
		  if(this.wholeDay){
			document.getElementById("timeDiv").setAttribute("class", "hide");
			//document.getElementById("endDayDiv").setAttribute("class", "hide");
		  }
		  else{
			document.getElementById("timeDiv").removeAttribute("class");
		  }
	  },
	  disableEndDay(){
		  this.singleDay = !this.singleDay;
			if(!this.singleDay){
				document.getElementById("cyclingDaysDiv").removeAttribute("class");
			}
			else{
				document.getElementById("cyclingDaysDiv").setAttribute("class", "hide");
			}
	  },
	  checkAlerts(){
		this.alerts = "";
		if(!this.singleDay && new Date(document.getElementById("startDay").value).getTime() > new Date(document.getElementById("endDay").value).getTime()){
			this.alerts += "Attenzione: la data di inizio è meno recente della data di fine<br>";
		}
		if(!this.wholeDay && parseInt(document.getElementById("endTime").value.split(":")[0]) !== 0 && parseInt(document.getElementById("startTime").value.split(":")[0]) > parseInt(document.getElementById("endTime").value.split(":")[0])){
			this.alerts += "Attenzione: l'ora di inizio è più avanti dell'ora di fine<br>";
		}
		if(this.cyclingDays && (document.getElementById("rotationType").value === "day" || document.getElementById("rotationType").value === "week") && date_diff(new Date(document.getElementById("startDay").value), new Date(document.getElementById("endDay").value)) > 60){
			this.alerts += "Per motivi di efficienza, non è possibile mostrare la rotazione giornaliera o settimanale per periodi superiori a 60 giorni."
		}
		else{
			
		}
	  },
	  showHeatMap(){
		this.heatMap = !this.heatMap;
		/*if(this.heatMap){
			document.getElementById("heatMapZones").removeAttribute("class");
			document.getElementById("heatMapZonesLabel").removeAttribute("class");
		}
		else{
			document.getElementById("heatMapZones").setAttribute("class", "hide");
			document.getElementById("heatMapZonesLabel").setAttribute("class", "hide");

		}*/
	  },
	  toggleAnimatedMarkers(){
		this.animatedMarkers = !this.animatedMarkers;
	  },
	  toggleCyclingDays(){
		this.cyclingDays = !this.cyclingDays;
		if(this.cyclingDays){
			document.getElementById("rotationTypeDiv").removeAttribute("class");
		}
		else{
			document.getElementById("rotationTypeDiv").setAttribute("class", "hide");
		}
	  },
	  startDayChange(){
		if(this.singleDay){
			document.getElementById("endDay").value = document.getElementById("startDay").value;
		}
		this.checkAlerts();
	  }
  }
}).mount('#app')

$(document).ready(function(){
	$("input").change(function(e){
		control_change();
	});
	$("select").change(function(e){
		control_change();
	});

});

/*Differenza tra due date in giorni */
function date_diff(date1, date2){
	date1 = new Date(date1);
	date2 = new Date(date2);
	return Math.abs(date1 - date2) / (1000*60*60*24);
}

function control_change(){
	let data = {
		"action": "change",
		"startDate": document.getElementById("startDay").value,
		"endDate": document.getElementById("endDay").value,
		"startHour": document.getElementById("startTime").value,
		"endHour": document.getElementById("endTime").value,
		"entireDay": document.getElementById("entireDay").checked,
		"singleDay": document.getElementById("singleDay").checked,
		"showHeatMap": document.getElementById("heatMap").checked,
		"animatedMarkers": document.getElementById("animatedMarkers").checked,
		"cyclingDays": document.getElementById("cyclingDays").checked,
		"rotationType": document.getElementById("rotationType").value
		//"heatMapZones": document.getElementById("heatMapZones").value
	};
	/*if(this.cyclingDays && document.getElementById("rotationType").value === "day" && date_diff(new Date(document.getElementById("startDay").value), new Date(document.getElementById("endDay"))) > 60){
		return;
	}*/
	$.post("ajax.php", data, function(result, status){
		if(status === "success"){
			if(result.length > 0){
				//console.log(result);
				if(document.getElementById("cyclingDays").checked){
					cycleDays(JSON.parse(result), parseInt(data["startHour"]), parseInt(data["endHour"]), data["entireDay"]);
				}	
				else{
					try{
						console.log("zava dati");
						console.log(parseInt(data["startHour"]))
						console.log(data["startHour"]);
						console.log(parseInt(data["startHour"]))
						console.log(data["endHour"]);
						console.log(data["entireDay"]);
						console.log(JSON.parse(result));

						showTrafficData(JSON.parse(result), parseInt(data["startHour"]), parseInt(data["endHour"]), data["entireDay"]);
					}
					catch{
						console.log("error on json parse");
					}
				}		
			}
		}
		else{
			console.log("Ajax error");
		}
	});	
}