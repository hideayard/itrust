<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PZEM-004T Clean Energy Monitoring Dashboard</title>
    
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com/"></script>
    <!-- Load Chart.js for graphing capabilities -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js" crossorigin="anonymous"></script>
    <!-- Load Leaflet for maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .metric-card {
            background-color: #1f2937;
            border: 1px solid #374151;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .battery-bar {
            transition: width 0.5s ease-in-out, background-color 0.5s ease-in-out;
        }
        #map {
            height: 400px;
            border-radius: 0.5rem;
            z-index: 1;
        }
        .leaflet-container {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen p-4 sm:p-8">

    <div class="max-w-7xl mx-auto">
        <!-- Header & Status Bar -->
        <header class="mb-10">
            <h1 class="text-4xl font-extrabold text-white text-center sm:text-left border-b border-gray-700 pb-3">
                PZEM-004T Clean Energy Monitor
            </h1>
            <p class="text-gray-400 text-center sm:text-left mt-2">
                Real-time AC Power System Metrics and Immediate Financial Impact
            </p>
            <div id="status-bar" class="mt-4 flex flex-col sm:flex-row justify-between text-sm p-3 bg-gray-800 rounded-lg border border-gray-700">
                <p id="auth-status" class="text-green-400 font-semibold">Connected to local data source</p>
                <p id="user-id-display" class="text-gray-500 truncate mt-1 sm:mt-0">Session ID: PZEM-DEMO-001</p>
                <p id="last-updated" class="text-gray-400 mt-1 sm:mt-0">Last Updated: <span id="last-updated-time">7:41:05 PM</span></p>
            </div>
        </header>

        <!-- Business Metrics Section -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            
            <!-- Projected Annual Savings -->
            <div class="md:col-span-2 bg-green-900/40 p-6 rounded-xl shadow-2xl border border-green-700 transition duration-300 hover:shadow-green-500/50">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-green-400">Projected Annual Savings</h2>
                    <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 4v4m0 4v2"></path></svg>
                </div>
                <div class="text-6xl font-extrabold tracking-tight text-white mt-3">RM 1,642.50</div>
                <p class="text-lg text-green-300 mt-2">
                    Capital retained yearly by switching from high-cost fossil fuels to efficient charging.
                </p>
            </div>

            <!-- Daily Cost Difference Card -->
            <div class="metric-card p-6 rounded-xl shadow-lg border-b-4 border-yellow-500 transition duration-300 hover:shadow-yellow-700/50">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-yellow-400">Cost Reduction</h2>
                    <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.001 0 0120.488 9z"></path></svg>
                </div>
                <div class="text-4xl font-bold tracking-tight text-white">90% Daily</div>
                <div class="text-lg font-light text-gray-400 mt-1">RM 4.50 saved per cycle</div>
            </div>

        </section>

        <!-- Main Chart Section (Multi-line Trend Analysis) -->
        <section class="mb-10 p-6 rounded-xl shadow-2xl bg-gray-800 border border-gray-700 transition duration-300 hover:shadow-lg hover:shadow-blue-500/30">
            <div class="flex justify-between items-center mb-4 flex-wrap gap-4">
                <h2 class="text-2xl font-semibold text-blue-400">Parameter Trend Analysis (Last 30 Readings)</h2>
                
                <!-- Parameter Selector Checkboxes -->
                <div id="param-selector" class="flex flex-wrap gap-x-4 gap-y-2 text-sm">
                    <label class="inline-flex items-center cursor-pointer text-green-400">
                        <input type="checkbox" value="power" checked onchange="handleParamChange()" class="form-checkbox h-4 w-4 text-green-500 rounded border-gray-700 bg-gray-700 focus:ring-green-500">
                        <span class="ml-2 font-medium">Power (W)</span>
                    </label>
                    <label class="inline-flex items-center cursor-pointer text-cyan-400">
                        <input type="checkbox" value="voltage" checked onchange="handleParamChange()" class="form-checkbox h-4 w-4 text-cyan-500 rounded border-gray-700 bg-gray-700 focus:ring-cyan-500">
                        <span class="ml-2 font-medium">Voltage (V)</span>
                    </label>
                    <label class="inline-flex items-center cursor-pointer text-red-400">
                        <input type="checkbox" value="current" checked onchange="handleParamChange()" class="form-checkbox h-4 w-4 text-red-500 rounded border-gray-700 bg-gray-700 focus:ring-red-500">
                        <span class="ml-2 font-medium">Current (A)</span>
                    </label>
                    <label class="inline-flex items-center cursor-pointer text-indigo-400">
                        <input type="checkbox" value="pf" onchange="handleParamChange()" class="form-checkbox h-4 w-4 text-indigo-500 rounded border-gray-700 bg-gray-700 focus:ring-indigo-500">
                        <span class="ml-2 font-medium">PF (Ratio)</span>
                    </label>
                </div>
            </div>
            
            <div class="h-64">
                <canvas id="powerChart"></canvas>
            </div>
        </section>

        <!-- Metrics Grid -->
        <main id="metrics-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">

            <!-- 1. Voltage -->
            <div class="metric-card p-6 rounded-xl shadow-lg transition duration-300 hover:shadow-cyan-700/50 hover:ring-2 ring-cyan-500/50" id="voltage-card">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-cyan-400">Voltage</h2>
                    <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <div class="text-5xl font-bold tracking-tight" id="voltage-value">230.38</div>
                <div class="text-2xl font-light text-gray-400 mt-1">Volts (V)</div>
            </div>

            <!-- 2. Current -->
            <div class="metric-card p-6 rounded-xl shadow-lg transition duration-300 hover:shadow-red-700/50 hover:ring-2 ring-red-500/50" id="current-card">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-red-400">Current</h2>
                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"></path></svg>
                </div>
                <div class="text-5xl font-bold tracking-tight" id="current-value">2.897</div>
                <div class="text-2xl font-light text-gray-400 mt-1">Amperes (A)</div>
            </div>

            <!-- 3. Active Power -->
            <div class="metric-card p-6 rounded-xl shadow-lg transition duration-300 hover:shadow-green-700/50 hover:ring-2 ring-green-500/50" id="power-card">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-green-400">Active Power</h2>
                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.976l-1.293 1.293-2.31 2.31"></path></svg>
                </div>
                <div class="text-5xl font-bold tracking-tight" id="power-value">658.7</div>
                <div class="text-2xl font-light text-gray-400 mt-1">Watts (W)</div>
            </div>

            <!-- 4. Power Factor -->
            <div class="metric-card p-6 rounded-xl shadow-lg transition duration-300 hover:shadow-indigo-700/50 hover:ring-2 ring-indigo-500/50" id="pf-card">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-indigo-400">Power Factor</h2>
                    <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v14h-3"></path></svg>
                </div>
                <div class="text-5xl font-bold tracking-tight" id="pf-value">0.987</div>
                <div class="text-2xl font-light text-gray-400 mt-1">Ratio</div>
            </div>
            
            <!-- 5. Frequency -->
            <div class="metric-card p-6 rounded-xl shadow-lg transition duration-300 hover:shadow-fuchsia-700/50 hover:ring-2 ring-fuchsia-500/50" id="frequency-card">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-fuchsia-400">Frequency</h2>
                    <svg class="w-6 h-6 text-fuchsia-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 13h10M7 17h10M7 9h10"></path></svg>
                </div>
                <div class="text-5xl font-bold tracking-tight" id="frequency-value">50.01</div>
                <div class="text-2xl font-light text-gray-400 mt-1">Hertz (Hz)</div>
            </div>

            <!-- 6. Total Energy (Persisted) -->
            <div class="metric-card p-6 rounded-xl shadow-lg transition duration-300 hover:shadow-yellow-700/50 hover:ring-2 ring-yellow-500/50" id="energy-card">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-yellow-400">Total Energy</h2>
                    <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M3 18h18M3 6h18"></path></svg>
                </div>
                <div class="text-5xl font-bold tracking-tight" id="energy-value">1250.55</div>
                <div class="text-2xl font-light text-gray-400 mt-1">Kilowatt-hours (kWh)</div>
            </div>

            <!-- 7. Battery Remaining Time (SoC Visual) -->
            <div class="metric-card p-6 rounded-xl shadow-lg transition duration-300 hover:shadow-orange-700/50 hover:ring-2 ring-orange-500/50" id="battery-card">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-orange-400">Battery Status (SoC)</h2>
                    <svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="flex items-center justify-between mb-2">
                    <div class="text-5xl font-bold tracking-tight" id="battery-time-value">04:32</div>
                    <div class="text-2xl font-bold text-orange-300" id="battery-soc-percent">95%</div>
                </div>
                <div class="text-2xl font-light text-gray-400 mb-2">Time Remaining (H:MM)</div>
                <div class="h-4 w-full bg-gray-700 rounded-full overflow-hidden">
                    <div id="battery-bar" class="h-full bg-green-500 rounded-full battery-bar" style="width: 95%;"></div>
                </div>
            </div>

            <!-- 8. Carbon Reduction (Calculated) -->
            <div class="metric-card p-6 rounded-xl shadow-lg transition duration-300 hover:shadow-sky-700/50 hover:ring-2 ring-sky-500/50" id="carbon-card">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-sky-400">CO₂ Reduction</h2>
                    <svg class="w-6 h-6 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 0020 14c0 1.576-.58 3.056-1.55 4.168M1.168 18.45A8.001 8.001 0 014 6h5"></path></svg>
                </div>
                <div class="text-5xl font-bold tracking-tight" id="carbon-reduction-value">731.57</div>
                <div class="text-2xl font-light text-gray-400 mt-1">kg CO₂</div>
            </div>
            
        </main>

        <!-- GPS Location (Map View) -->
        <section class="p-6 rounded-xl shadow-2xl bg-gray-800 border border-gray-700 transition duration-300 hover:shadow-lg hover:shadow-purple-500/30">
            <h2 class="text-2xl font-semibold mb-4 text-purple-400">Device Location (Johor, Malaysia)</h2>
            <div id="map" class="w-full rounded-lg overflow-hidden mb-3"></div>
            <p class="text-lg text-gray-300" id="coordinates-text">Current: 1.5501 N, 103.7800 E</p>
        </section>
    </div>

    <script>
        // --- GLOBAL STATE AND CONFIGURATION ---
        let simulationInterval;
        let map = null;
        let marker = null;

        // Configuration for the chart metrics
        const METRIC_CONFIG = {
            voltage: { label: 'Voltage', unit: 'Volts (V)', color: '#06b6d4', checked: true },
            current: { label: 'Current', unit: 'Amperes (A)', color: '#f87171', checked: true },
            power: { label: 'Active Power', unit: 'Watts (W)', color: '#10b981', checked: true },
            pf: { label: 'Power Factor', unit: 'Ratio', color: '#818cf8', checked: false }
        };

        // Simulated Persistent Data
        let totalEnergy = 1250.55; 
        const BATTERY_CAPACITY_WH = 1280; 
        let batteryStateOfCharge = 0.95; 
        const CARBON_REDUCTION_FACTOR = 0.585;

        // Simulated GPS Constants (Johor, Malaysia)
        const BASE_LAT = 1.5501;
        const BASE_LON = 103.7800;
        
        // Historical data storage
        let historicalData = [];
        const MAX_HISTORY_POINTS = 30;
        
        // DOM Element Mapping
        const elements = {
            voltage: document.getElementById('voltage-value'),
            current: document.getElementById('current-value'),
            power: document.getElementById('power-value'),
            energy: document.getElementById('energy-value'),
            frequency: document.getElementById('frequency-value'),
            pf: document.getElementById('pf-value'),
            batteryTime: document.getElementById('battery-time-value'),
            batterySoC: document.getElementById('battery-soc-percent'),
            batteryBar: document.getElementById('battery-bar'),
            carbonReduction: document.getElementById('carbon-reduction-value'),
            coordinatesText: document.getElementById('coordinates-text'),
            authStatus: document.getElementById('auth-status'),
            userIdDisplay: document.getElementById('user-id-display'),
            lastUpdated: document.getElementById('last-updated-time'),
            paramSelector: document.getElementById('param-selector'),
        };

        let powerChart;
        
        // --- INITIALIZE MAP ---
        function initMap() {
            map = L.map('map').setView([BASE_LAT, BASE_LON], 15);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19,
            }).addTo(map);
            
            // Add custom icon for solar installation
            const solarIcon = L.divIcon({
                className: 'solar-marker',
                html: '<div style="background-color: #fbbf24; width: 24px; height: 24px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 10px rgba(251, 191, 36, 0.8);"></div>',
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            });
            
            marker = L.marker([BASE_LAT, BASE_LON], { icon: solarIcon })
                .addTo(map)
                .bindPopup('<b>PZEM-004T Installation</b><br>Solar Monitoring Station<br>Johor, Malaysia')
                .openPopup();
        }

        // --- GENERATE DUMMY HISTORICAL DATA ---
        function generateHistoricalData() {
            const now = Date.now();
            const data = [];
            
            for (let i = MAX_HISTORY_POINTS - 1; i >= 0; i--) {
                const timestamp = now - (i * 2000); // 2-second intervals
                const timeString = new Date(timestamp).toLocaleTimeString('en-US', { 
                    hour: '2-digit', 
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false 
                });
                
                // Generate realistic fluctuations
                const baseVoltage = 230.5;
                const baseCurrent = 2.8;
                const basePF = 0.99;
                
                const voltage = baseVoltage + (Math.random() * 0.8 - 0.4);
                const current = baseCurrent + (Math.random() * 0.3 - 0.15);
                const pf = Math.min(1, Math.max(0.95, basePF + (Math.random() * 0.006 - 0.003)));
                const power = (voltage * current * pf);
                
                // Simulate some variations in the data
                const lat = BASE_LAT + (Math.random() * 0.0002 - 0.0001);
                const lon = BASE_LON + (Math.random() * 0.0002 - 0.0001);
                
                data.push({
                    timestamp,
                    timeString,
                    voltage: parseFloat(voltage.toFixed(2)),
                    current: parseFloat(current.toFixed(3)),
                    power: parseFloat(power.toFixed(1)),
                    pf: parseFloat(pf.toFixed(3)),
                    latitude: lat,
                    longitude: lon
                });
            }
            
            return data;
        }

        // --- DUMMY DATA SIMULATION LOGIC ---
        function readSensorData() {
            const intervalSeconds = 2;
            const intervalHours = intervalSeconds / 3600;

            // Base values for simulation
            const baseVoltage = 230.5;
            const baseCurrent = 2.8;
            const basePF = 0.99;
            const baseFrequency = 50.0;

            const voltage = parseFloat((baseVoltage + (Math.random() * 0.8 - 0.4)).toFixed(2));
            const current = parseFloat((baseCurrent + (Math.random() * 0.3 - 0.15)).toFixed(3));
            const frequency = parseFloat((baseFrequency + (Math.random() * 0.06 - 0.03)).toFixed(2));
            const powerFactor = parseFloat((basePF + (Math.random() * 0.006 - 0.003)).toFixed(3));

            // Calculate active power
            const consumedPowerW = (voltage * current * powerFactor);
            const power = parseFloat(consumedPowerW.toFixed(1));

            // Update persistent state (Energy and Battery)
            const energyDelta = (consumedPowerW * intervalHours) / 1000;
            totalEnergy += energyDelta;
            const energy = totalEnergy.toFixed(2);

            // Calculate Carbon Reduction (based on total energy generated/saved)
            const carbonReduction = (totalEnergy * CARBON_REDUCTION_FACTOR).toFixed(2);

            // Battery State of Charge (SoC) calculation
            const consumedWh = consumedPowerW * intervalHours;
            const socDelta = consumedWh / BATTERY_CAPACITY_WH;
            batteryStateOfCharge -= socDelta;
            batteryStateOfCharge = Math.max(0, Math.min(1, batteryStateOfCharge)); 
            
            // Calculate time remaining (H:MM)
            let timeRemainingHours = 0;
            if (consumedPowerW > 5 && batteryStateOfCharge > 0.01) { 
                const remainingWh = batteryStateOfCharge * BATTERY_CAPACITY_WH;
                timeRemainingHours = remainingWh / consumedPowerW;
            } else if (batteryStateOfCharge <= 0.01) {
                timeRemainingHours = 0;
            } else {
                timeRemainingHours = Infinity; 
            }

            let batteryTime;
            if (timeRemainingHours === Infinity) {
                batteryTime = ">99:59";
            } else if (timeRemainingHours === 0) {
                batteryTime = "00:00";
            } else {
                const hours = Math.floor(timeRemainingHours);
                const minutes = Math.floor((timeRemainingHours % 1) * 60);
                batteryTime = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
            }
            
            // GPS Simulation (small fluctuations around a base point)
            const latitude = BASE_LAT + (Math.random() * 0.0002 - 0.0001);
            const longitude = BASE_LON + (Math.random() * 0.0002 - 0.0001);

            return { 
                voltage, current, power, energy, frequency, pf: powerFactor, 
                batteryTime, batterySoC: batteryStateOfCharge, carbonReduction, 
                latitude: parseFloat(latitude.toFixed(4)), 
                longitude: parseFloat(longitude.toFixed(4))
            };
        }

        // --- CHART FUNCTIONS ---
        function getSelectedMetrics() {
            const checkboxes = elements.paramSelector.querySelectorAll('input[type="checkbox"]');
            const selected = [];
            checkboxes.forEach(cb => {
                if (cb.checked) {
                    selected.push(cb.value);
                }
            });
            return selected.length > 0 ? selected : ['power'];
        }

        function updateChartData(data) {
            const timeLabels = data.map(d => d.timeString);
            const selectedMetrics = getSelectedMetrics();
            const newDatasets = [];

            selectedMetrics.forEach((metricKey) => {
                const config = METRIC_CONFIG[metricKey];
                const metricData = data.map(d => d[metricKey]);
                
                const ctx = document.getElementById('powerChart').getContext('2d');
                const gradient = ctx.createLinearGradient(0, 0, 0, 250);
                gradient.addColorStop(0, `${config.color}44`);
                gradient.addColorStop(1, `${config.color}00`);

                const isElectricMetric = metricKey !== 'pf';

                newDatasets.push({
                    label: `${config.label}`,
                    data: metricData,
                    borderColor: config.color,
                    backgroundColor: isElectricMetric ? gradient : 'transparent',
                    borderWidth: 3,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    tension: 0.4,
                    fill: isElectricMetric ? 'start' : false,
                    yAxisID: isElectricMetric ? 'electric-y-axis' : 'ratio-y-axis'
                });
            });

            if (powerChart) {
                powerChart.data.labels = timeLabels;
                powerChart.data.datasets = newDatasets;
                
                const scales = {
                    x: {
                        display: true, 
                        title: { display: true, text: 'Time', color: '#9ca3af', font: { size: 14, weight: '600' } }, 
                        grid: { color: 'rgba(55, 65, 81, 0.5)', drawBorder: true }, 
                        ticks: { color: '#d1d5db', maxRotation: 45, minRotation: 45 }
                    }
                };
                
                const hasElectricMetric = selectedMetrics.some(m => m !== 'pf');
                if (hasElectricMetric) {
                    scales['electric-y-axis'] = {
                        display: true,
                        position: 'left',
                        title: { display: true, text: 'V / A / W', color: '#06b6d4', font: { size: 14, weight: '600' } },
                        grid: { color: 'rgba(55, 65, 81, 0.5)' },
                        ticks: { color: '#d1d5db' }
                    };
                }

                const hasPfMetric = selectedMetrics.includes('pf');
                if (hasPfMetric) {
                     scales['ratio-y-axis'] = {
                        display: true,
                        position: hasElectricMetric ? 'right' : 'left',
                        title: { display: true, text: 'Power Factor (Ratio)', color: '#818cf8', font: { size: 14, weight: '600' } },
                        min: 0.9,
                        max: 1.05,
                        grid: { 
                            color: hasElectricMetric ? 'rgba(55, 65, 81, 0.1)' : 'rgba(55, 65, 81, 0.5)',
                            drawOnChartArea: hasElectricMetric ? false : true,
                        },
                        ticks: { color: '#d1d5db' }
                    };
                }
                
                powerChart.options.scales = scales;
                powerChart.options.plugins.legend.display = selectedMetrics.length > 1;
                
                powerChart.update('none');
            }
        }

        function initChart() {
            const ctx = document.getElementById('powerChart').getContext('2d');
            
            powerChart = new Chart(ctx, {
                type: 'line',
                data: { labels: [], datasets: [] },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 0 },
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { 
                            display: true, 
                            labels: { 
                                color: '#d1d5db', 
                                font: { size: 14 }
                            } 
                        },
                        tooltip: { 
                            mode: 'index', 
                            intersect: false, 
                            backgroundColor: 'rgba(31, 41, 55, 0.9)', 
                            titleColor: '#ffffff', 
                            bodyColor: '#e5e7eb', 
                            borderColor: '#4b5563', 
                            borderWidth: 1,
                            cornerRadius: 8,
                            padding: 12
                        }
                    },
                    scales: {}
                }
            });
            
            // Generate initial historical data
            historicalData = generateHistoricalData();
            updateChartData(historicalData);
        }

        // --- UPDATE UI FUNCTIONS ---
        function updateInstantaneousMetrics(data) {
            elements.voltage.textContent = data.voltage.toFixed(2);
            elements.current.textContent = data.current.toFixed(3);
            elements.power.textContent = data.power.toFixed(1);
            elements.energy.textContent = data.energy;
            elements.frequency.textContent = data.frequency;
            elements.pf.textContent = data.pf.toFixed(3);
            
            elements.batteryTime.textContent = data.batteryTime;
            elements.carbonReduction.textContent = data.carbonReduction;

            // Battery Visuals
            const socPercent = (data.batterySoC * 100).toFixed(0);
            elements.batterySoC.textContent = `${socPercent}%`;
            elements.batteryBar.style.width = `${socPercent}%`;
            
            if (data.batterySoC < 0.20) {
                elements.batteryBar.className = 'h-full bg-red-600 rounded-full battery-bar';
            } else if (data.batterySoC < 0.50) {
                elements.batteryBar.className = 'h-full bg-yellow-500 rounded-full battery-bar';
            } else {
                elements.batteryBar.className = 'h-full bg-green-500 rounded-full battery-bar';
            }

            // Update map marker position
            if (marker && map) {
                marker.setLatLng([data.latitude, data.longitude]);
            }
            
            // Update coordinates text
            const latString = data.latitude.toFixed(4) + (data.latitude >= 0 ? ' N' : ' S');
            const lonString = data.longitude.toFixed(4) + (data.longitude >= 0 ? ' E' : ' W');
            elements.coordinatesText.textContent = `Current: ${latString}, ${lonString}`;
            
            // Update timestamp
            elements.lastUpdated.textContent = new Date().toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit',
                second: '2-digit',
                hour12: false 
            });
        }

        function simulateDataUpdate() {
            const data = readSensorData();
            
            // Add new data to historical array
            const now = new Date();
            const newDataPoint = {
                timestamp: now.getTime(),
                timeString: now.toLocaleTimeString('en-US', { 
                    hour: '2-digit', 
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false 
                }),
                voltage: data.voltage,
                current: data.current,
                power: data.power,
                pf: data.pf,
                latitude: data.latitude,
                longitude: data.longitude
            };
            
            historicalData.push(newDataPoint);
            if (historicalData.length > MAX_HISTORY_POINTS) {
                historicalData.shift();
            }
            
            updateChartData(historicalData);
            updateInstantaneousMetrics(data);
        }

        // --- INITIALIZATION ---
        function initApplication() {
            // Initialize map
            initMap();
            
            // Initialize chart
            initChart();
            
            // Set initial data
            const initialData = readSensorData();
            updateInstantaneousMetrics(initialData);
            
            // Start simulation
            simulationInterval = setInterval(simulateDataUpdate, 2000);
            
            console.log('Dashboard initialized with local data simulation');
        }

        // --- EVENT HANDLERS ---
        window.handleParamChange = function() {
            updateChartData(historicalData);
        }

        // --- START APPLICATION ---
        document.addEventListener('DOMContentLoaded', initApplication);
    </script>

</body>
</html>