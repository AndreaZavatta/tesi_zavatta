const app = Vue.createApp({
    data() {
        return {
            cons: [],
            consProps: {},
            sed: [],
            sedProps: {},
            groups: [],
            groupsProps: {},
            tableToShow: "sedute",
            selectedGroups: [],
            mandNumToYear: ["Tutti i mandati"],
            consTableRowNotPrimaryColor: [],
            showConsFilters: false
        }
    },
    mounted: function () {
        this.loadData();
    },
    methods: {
        loadData() {
            fetch('./server.php/api/data')
                .then(response => response.json())
                .then(jsonData => {
                    const elections = ["2016-06-05", "2021-10-04"];
                    for (let i = 1; i < elections.length + 1; i++) {
                        if ((i - 1) == 0) {
                            this.mandNumToYear.push("2011 - " + elections[i - 1].split("-")[0]);
                        } else {
                            this.mandNumToYear.push(elections[i - 2].split("-")[0] + " - " + elections[i - 1].split("-")[0]);
                        }
                    }
                    this.mandNumToYear.push(elections[elections.length - 1].split("-")[0] + " - 2026");

                    jsonData.forEach((c) => {
                        if (!this.cons.includes(c.nominativo)) {
                            this.cons.push(c.nominativo);
                        }
                        if (!this.sed.includes(c.data_seduta)) {
                            this.sed.push(c.data_seduta);
                        }
                        if (!this.groups.includes(c.gruppo_politico)) {
                            this.groups.push(c.gruppo_politico);
                        }

                        let mand = 1;
                        elections.forEach((e, i) => {
                            if (c.data_seduta > e) {
                                mand = i + 2;
                            }
                        });

                        // Handle consProps
                        if (!(c.nominativo in this.consProps)) {
                            this.consProps[c.nominativo] = { gruppo: c.gruppo_politico, mand: [{ numP: 0, numA: 0 }], showInfo: false };
                            for (let i = 0; i < elections.length + 1; i++) {
                                this.consProps[c.nominativo].mand[i + 1] = { numP: 0, numA: 0 };
                            }
                        }

                        // Handle sedProps
                        let currSed = { numP: 0, numA: 0 };
                        if (c.data_seduta in this.sedProps) {
                            currSed = this.sedProps[c.data_seduta];
                        }
                        if (c.presenza == "P") {
                            currSed.numP++;
                            this.consProps[c.nominativo].mand[0].numP++;
                            this.consProps[c.nominativo].mand[mand].numP++;
                        } else {
                            currSed.numA++;
                            this.consProps[c.nominativo].mand[0].numA++;
                            this.consProps[c.nominativo].mand[mand].numA++;
                        }
                        this.sedProps[c.data_seduta] = currSed;

                        // Handle groupsProps
                        let currGroup = { num: 1, members: [c.nominativo], show: true, mand: [{ totVot: c.num_votazioni, totPres: (c.presenza == "P" ? 1 : 0) }] };
                        if (c.gruppo_politico in this.groupsProps) {
                            currGroup = this.groupsProps[c.gruppo_politico];
                            if (!currGroup.members.includes(c.nominativo)) {
                                currGroup.num = this.groupsProps[c.gruppo_politico].num + 1;
                                currGroup.members.push(c.nominativo);
                            }
                            if (c.presenza == "P") {
                                currGroup.mand[0].totPres++;
                            }
                            currGroup.mand[0].totVot += c.num_votazioni;
                        }
                        if (currGroup.mand[mand] != undefined) {
                            currGroup.mand[mand].totVot += c.num_votazioni;
                            if (c.presenza == "P") {
                                currGroup.mand[mand].totPres++;
                            }
                        } else {
                            currGroup.mand[mand] = { totVot: c.num_votazioni, totPres: (c.presenza == "P" ? 1 : 0) };
                        }
                        this.groupsProps[c.gruppo_politico] = currGroup;
                    });

                    this.cons.sort();
                    this.sed.sort().reverse(); // Reverse to have the latest session first
                    this.groups.sort();

                    this.selectedGroups = this.groups;

                    const sedLabels = [];
                    const sedPres = [];
                    const sedAbs = [];
                    for (let i = 0; i < this.sed.length; i++) {
                        const sed = this.sed[i];
                        if (this.sedProps[sed]) {
                            sedLabels.push(sed);
                            sedPres.push(this.sedProps[sed].numP || 0);
                            sedAbs.push(this.sedProps[sed].numA || 0);
                        } else {
                            console.warn(`No data found for session: ${sed}`);
                        }
                    }

                    const graphsOptions = {
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                stacked: true
                            },
                            y: {
                                beginAtZero: true,
                                stacked: true
                            }
                        }
                    };

                    const presChart = new Chart("pres-chart", {
                        type: "bar",
                        data: {
                            labels: sedLabels,
                            datasets: [{
                                label: "Numero presenti",
                                data: sedPres
                            }, {
                                label: "Numero assenti",
                                data: sedAbs
                            }]
                        },
                        options: graphsOptions
                    });
                    document.querySelector("div#sedute-chart-div > div:nth-child(2) > div").style.width = (sedLabels.length * 3.5) + "rem";

                    const consPres = [];
                    const consAbs = [];
                    this.cons.forEach(con => {
                        consPres.push(this.consProps[con].mand[0].numP);
                        consAbs.push(this.consProps[con].mand[0].numA);
                    });

                    const consChart = new Chart("cons-chart", {
                        type: "bar",
                        data: {
                            labels: this.cons,
                            datasets: [{
                                label: "Numero presenze",
                                data: consPres
                            }, {
                                label: "Numero assenze",
                                data: consAbs
                            }]
                        },
                        options: graphsOptions
                    });
                    document.querySelector("div#consiglieri-chart-div > div:nth-child(2) > div").style.width = (this.cons.length * 3.5) + "rem";

                    const groupVotes = [];
                    const groupPres = [];
                    this.groups.forEach(group => {
                        groupVotes.push(this.groupsProps[group].mand[0].totVot);
                        groupPres.push(this.groupsProps[group].mand[0].totPres);
                    });

                    const groupsPresChart = new Chart("groups-pres-chart", {
                        type: "bar",
                        data: {
                            labels: this.groups,
                            datasets: [{
                                label: "Numero presenze",
                                data: groupPres
                            }]
                        },
                        options: graphsOptions
                    });
                    document.querySelector("div#groups-pres-chart-div > div").style.width = (this.groups.length * 3.5) + "rem";

                    const groupsVotesChart = new Chart("groups-votes-chart", {
                        type: "bar",
                        data: {
                            labels: this.groups,
                            datasets: [{
                                label: "Numero voti",
                                data: groupVotes
                            }]
                        },
                        options: graphsOptions
                    });
                    document.querySelector("div#groups-votes-chart-div > div").style.width = (this.groups.length * 3.5) + "rem";
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                });
        },
        clickedSed(date) {
            window.location.href = "./sedute/seduta.html?s=" + date;
        },
        updateView(newValue) {
            this.tableToShow = newValue;
        },
        isNotPrimaryTd(i) {
            return this.consTableRowNotPrimaryColor[i];
        },
        updateShowConsFilters(newValue) {
            this.showConsFilters = newValue;
        }
    },
    watch: {
        selectedGroups: function (newValue) {
            this.groups.forEach(g => {
                this.groupsProps[g].show = this.selectedGroups.includes(g) ? true : false;
            });
            let notPrimaryTd = false;
            this.cons.forEach((c, i) => {
                if (this.groupsProps[this.consProps[c].gruppo].show) {
                    this.consTableRowNotPrimaryColor[i] = notPrimaryTd;
                    notPrimaryTd = !notPrimaryTd;
                }
            });
        }
    }
});

app.component('mobile-nav', {
    props: {
        tts: String
    },
    template: `
        <nav id="nav-mobile" class="m-0 p-0 row">
            <ul class="m-0 p-0 row col-12">
                <li class="m-0 p-0 row col-3">
                    <button class="col-12 m-0" :class="{'selected-button': tts == 'sedute'}" id="sedute-button" @click="changeTable('sedute')">Sedute</button>
                </li>
                <li class="m-0 p-0 row col-3">
                    <button class="col-12 m-0" :class="{'selected-button': tts == 'consiglieri'}" id="consiglieri-button" @click="changeTable('consiglieri')">Consiglieri</button>
                </li>
                <li class="m-0 p-0 row col-3">
                    <button class="col-12 m-0" :class="{'selected-button': tts == 'gruppi'}" id="gruppi-button" @click="changeTable('gruppi')">Gruppi</button>
                </li>
                <li class="m-0 p-0 row col-3">
                    <button class="col-12 m-0" :class="{'selected-button': tts == 'dashboard'}" id="dashboard-button" @click="goToDashboard()">Dashboard</button>
                </li>
            </ul>
        </nav>`,
    methods: {
        changeTable(id) {
            this.$emit("uv", id);
        },
        goToDashboard() {
            window.location.href = '../dashboard/dashboard.php'; // Replace 'dashboard.html' with the actual URL
        }
    }
});

app.component('desktop-nav', {
    props: {
        tts: String
    },
    template: `
        <nav id="nav-desktop" class="col-md-2">
            <ul class="m-0 p-0 row col-12">
                <li class="m-0 p-0 row col-12">
                    <button :class="{'selected-button': tts == 'sedute'}" id="sedute-desktop-button" @click="changeTable('sedute')">Sedute</button>
                </li>
                <li class="m-0 p-0 row col-12">
                    <button :class="{'selected-button': tts == 'consiglieri'}" id="consiglieri-desktop-button" @click="changeTable('consiglieri')">Consiglieri</button>
                </li>
                <li class="m-0 p-0 row col-12">
                    <button :class="{'selected-button': tts == 'gruppi'}" id="gruppi-desktop-button" @click="changeTable('gruppi')">Gruppi</button>
                </li>
                <li class="m-0 p-0 row col-12">
                    <button :class="{'selected-button': tts == 'dashboard'}" id="dashboard-desktop-button" @click="goToDashboard()">Dashboard</button>
                </li>
            </ul>
        </nav>`,
    methods: {
        changeTable(id) {
            this.$emit("uv", id);
        },
        goToDashboard() {
            window.location.href = '../dashboard/dashboard.php'; // Replace 'dashboard.html' with the actual URL
        }
    }
});

app.component('groups-charts', {
    props: {
        groups: Array,
        gprps: Object,
        mandnty: Array
    },
    template: `
        <div id="gruppi-chart-div">
            <nav class="row m-0 p-0">
                <ul class="col-12 row m-0 p-0">
                    <li class="col-6 row m-0 p-0">
                        <button id="groups-pres-button" class="col-12 m-0 p-0" :class="{'selected-groups-chart-button': graphToShow == 'groups-pres'}" @click="changeChart('groups-pres')">Presenze per gruppo</button>
                    </li>
                    <li class="col-6 row m-0 p-0">
                        <button id="groups-votes-button" class="col-12 m-0 p-0" :class="{'selected-groups-chart-button': graphToShow == 'groups-votes'}" @click="changeChart('groups-votes')">Votazioni per gruppo</button>
                    </li>
                </ul>
            </nav>

            <div id="mand-div">
                <p><label for="mand">Mandato:</label></p>
                <select name="mand" id="mand" v-model="mandato">
                    <option v-for="(m, i) in mandnty" :value="i">{{m}}</option>
                </select>

            </div>

            <div id="groups-pres-chart-div" class="groups-chart-div" v-show="graphToShow == 'groups-pres'">
                <div>
                    <canvas id="groups-pres-chart"></canvas>
                </div>
            </div>

            <div id="groups-votes-chart-div" class="groups-chart-div" v-show="graphToShow == 'groups-votes'">
                <div>
                    <canvas id="groups-votes-chart"></canvas>
                </div>
            </div>
        </div>`,
    methods: {
        changeChart(id){
            this.graphToShow = id;
            this.updateCharts();
        },
        updateCharts() {
            const groupVotes = [];
            const groupPres = [];
            const labels = [];
            this.groups.forEach(group => {
                if (this.gprps[group].mand[this.mandato] != undefined) {
                    groupVotes.push(this.gprps[group].mand[this.mandato].totVot);
                    groupPres.push(this.gprps[group].mand[this.mandato].totPres);
                    labels.push(group);
                }
            });
            const graphsOptions = {
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true
                    },
                    y: {
                        beginAtZero: true,
                        stacked: true
                    }
                }
            };

            if (Chart.getChart("groups-pres-chart")) {
                Chart.getChart("groups-pres-chart").destroy();
            }
            new Chart("groups-pres-chart", {
                type: "bar",
                data: {
                    labels: labels,
                    datasets: [{
                        label: "Numero presenze",
                        data: groupPres
                    }]
                },
                options: graphsOptions
            });
            document.querySelector("div#groups-pres-chart-div > div").style.width = (labels.length * 3.5) + "rem";

            if (Chart.getChart("groups-votes-chart")) {
                Chart.getChart("groups-votes-chart").destroy();
            }
            new Chart("groups-votes-chart", {
                type: "bar",
                data: {
                    labels: labels,
                    datasets: [{
                        label: "Numero voti",
                        data: groupVotes
                    }]
                },
                options: graphsOptions
            });
            document.querySelector("div#groups-votes-chart-div > div").style.width = (labels.length * 3.5) + "rem";
        }
    },
    data() {
        return {
            mandato: 0,
            graphToShow: "groups-pres"
        };
    },
    watch: {
        mandato: function(newValue) {
            this.updateCharts();
        }
    }
});

app.mount('#app');
