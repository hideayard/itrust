<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>TradingView Market Dashboard</title>
    <script
        type="text/javascript"
        src="https://s3.tradingview.com/tv.js"></script>
    <style>
        :root {
            --bg-primary: #131722;
            --bg-secondary: #1e222d;
            --bg-widget: #1e222d;
            --border-color: #2a2e39;
            --text-primary: #d1d4dc;
            --text-secondary: #787b86;
            --accent-blue: #2962ff;
            --accent-green: #26a69a;
            --accent-red: #ef5350;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
                Oxygen, Ubuntu, sans-serif;
        }

        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
        }

        .dashboard-header {
            background: var(--bg-secondary);
            border-radius: 12px;
            padding: 20px 30px;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-left h1 {
            font-size: 1.8rem;
            background: linear-gradient(90deg,
                    var(--accent-blue),
                    var(--accent-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }

        .header-left p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .controls {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .control-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .control-group label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        select,
        button {
            background: var(--bg-widget);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
            cursor: pointer;
            min-width: 120px;
        }

        select:focus,
        button:focus {
            outline: none;
            border-color: var(--accent-blue);
        }

        button {
            background: var(--accent-blue);
            border: none;
            font-weight: 600;
            transition: all 0.2s;
            min-width: 100px;
        }

        button:hover {
            background: #1a4cff;
            transform: translateY(-1px);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            height: calc(100vh - 150px);
        }

        .main-widget {
            grid-column: 1;
            grid-row: 1;
            background: var(--bg-widget);
            border-radius: 12px;
            padding: 0;
            border: 1px solid var(--border-color);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .main-widget-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .main-widget-header h2 {
            font-size: 1.2rem;
        }

        .chart-container {
            flex: 1;
            min-height: 0;
        }

        #chart {
            width: 100%;
            height: 100%;
        }

        .sidebar-widgets {
            display: flex;
            flex-direction: column;
            gap: 20px;
            max-height: 100%;
        }

        .widget {
            background: var(--bg-widget);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .widget-header {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .widget-header h2 {
            font-size: 1.1rem;
        }

        .widget-content {
            flex: 1;
            min-height: 0;
            padding: 0;
        }

        .widget-content iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            text-align: center;
            padding: 20px;
            background: linear-gradient(45deg,
                    rgba(41, 98, 255, 0.05) 0%,
                    rgba(38, 166, 154, 0.05) 100%);
        }

        .placeholder h3 {
            margin-bottom: 10px;
            color: var(--text-primary);
        }

        .placeholder p {
            margin: 5px 0;
            font-size: 0.9rem;
            max-width: 300px;
        }

        .placeholder a {
            color: var(--accent-blue);
            text-decoration: none;
        }

        .placeholder a:hover {
            text-decoration: underline;
        }

        .widget-grid {
            display: grid;
            gap: 20px;
            height: 100%;
        }

        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
                height: auto;
            }

            .sidebar-widgets {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: stretch;
            }

            .controls {
                flex-direction: column;
                align-items: stretch;
            }

            select,
            button {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <header class="dashboard-header">
            <div class="header-left">
                <h1>📈 Market Analysis Dashboard</h1>
                <p>
                    Advanced real-time charts, market data, economic calendar, and news
                    in one unified view
                </p>
            </div>
            <div class="controls">
                <div class="control-group">
                    <label for="pair">Currency Pair</label>
                    <select id="pair">
                        <option value="EURJPY">EUR/JPY</option>
                        <option value="EURUSD">EUR/USD</option>
                        <option value="GBPUSD">GBP/USD</option>
                        <option value="USDJPY">USD/JPY</option>
                        <option value="BTCUSD">BTC/USD</option>
                    </select>
                </div>
                <div class="control-group">
                    <label for="tf">Timeframe</label>
                    <select id="tf">
                        <option value="15">15min</option>
                        <option value="30">30min</option>
                        <option value="60">1H</option>
                        <option value="240" selected>4H</option>
                        <option value="D">Daily</option>
                        <option value="W">Weekly</option>
                    </select>
                </div>
                <div class="control-group">
                    <label>&nbsp;</label>
                    <button onclick="loadChart()">Load Chart</button>
                </div>
            </div>
        </header>

        <main class="dashboard-grid">
            <section class="main-widget">
                <div class="main-widget-header">
                    <h2 id="current-pair">EURJPY - 4H Chart</h2>
                    <div class="status-indicator">
                        <span style="color: var(--accent-green)">●</span> Live
                    </div>
                </div>
                <div class="chart-container">
                    <div id="chart"></div>
                </div>
            </section>

            <div class="sidebar-widgets">
                <section class="widget">
                    <div class="widget-header">
                        <h2>Market Data Watchlist</h2>
                    </div>
                    <div class="widget-content">
                        <div class="placeholder">
                            <script
                                type="module"
                                src="https://widgets.tradingview-widget.com/w/en/tv-market-summary.js"></script>

                            <tv-market-summary
                                symbol-sectors='[{"sectionName":"Currency","symbols":["OANDA:EURJPY","OANDA:EURUSD","OANDA:USDJPY","OANDA:GBPUSD","OANDA:GBPJPY"]},{"sectionName":"Crypto","symbols":["BINANCEUS:BTCUSDT","BINANCEUS:ETHUSDT","BINANCEUS:XRPUSDT","BINANCEUS:SOLUSDT","OKX:HYPEUSDT","BINANCE:BNBUSDT","CRYPTOCAP:TOTAL3","OKX:XAUTUSDT"]},{"sectionName":"Stocks","symbols":["SPREADEX:SPX","NASDAQ:TSLA","NASDAQ:NVDA","NASDAQ:GOOGL","FXOPEN:DXY","IDX:BBCA","IDX:COMPOSITE","IDX:ANTM","IDX:BBRI"]},{"sectionName":"Commodity","symbols":["FOREXCOM:SPXUSD","FOREXCOM:NSXUSD","FOREXCOM:DJI","FOREXCOM:UKXGBP"]}]'
                                direction="vertical" item-size="compact" mode="custom" style="width: 400px; height: 500px"></tv-market-summary>
                        </div>
                    </div>
                </section>

                <section class="widget">
                    <div class="widget-header">
                        <h2>Economic Calendar</h2>
                    </div>
                    <div class="widget-content">
                        <div class="placeholder">
                            <!-- TradingView Widget BEGIN -->
                            <div class="tradingview-widget-container">
                                <div class="tradingview-widget-container__widget"></div>
                                <div class="tradingview-widget-copyright">
                                    <a
                                        href="https://www.tradingview.com/economic-calendar/"
                                        rel="noopener nofollow"
                                        target="_blank"><span class="blue-text">Economic Calendar</span></a><span class="trademark"> by TradingView</span>
                                </div>
                                <script
                                    type="text/javascript"
                                    src="https://s3.tradingview.com/external-embedding/embed-widget-events.js"
                                    async>
                                    {
                                        "colorTheme": "dark",
                                        "isTransparent": false,
                                        "locale": "en",
                                        "countryFilter": "ar,au,br,ca,cn,fr,de,in,id,it,jp,kr,mx,ru,sa,za,tr,gb,us,eu",
                                        "importanceFilter": "-1,0,1",
                                        "width": 400,
                                        "height": 550
                                    }
                                </script>
                            </div>
                            <!-- TradingView Widget END -->
                        </div>
                    </div>
                </section>

                <section class="widget">
                    <div class="widget-header">
                        <h2>Financial News</h2>
                    </div>
                    <div class="widget-content">
                        <div class="placeholder">
                            <!-- TradingView Widget BEGIN -->
                            <div class="tradingview-widget-container">
                                <div class="tradingview-widget-container__widget"></div>
                                <div class="tradingview-widget-copyright">
                                    <a
                                        href="https://www.tradingview.com/news/top-providers/tradingview/"
                                        rel="noopener nofollow"
                                        target="_blank"><span class="blue-text">Top stories</span></a><span class="trademark"> by TradingView</span>
                                </div>
                                <script
                                    type="text/javascript"
                                    src="https://s3.tradingview.com/external-embedding/embed-widget-timeline.js"
                                    async>
                                    {
                                        "displayMode": "regular",
                                        "feedMode": "market",
                                        "colorTheme": "dark",
                                        "isTransparent": false,
                                        "locale": "en",
                                        "market": "forex",
                                        "width": 400,
                                        "height": 550
                                    }
                                </script>
                            </div>
                            <!-- TradingView Widget END -->
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script>
        function loadChart() {
            const symbol = document.getElementById("pair").value;
            const tf = document.getElementById("tf").value;

            // Update current pair display
            document.getElementById(
                "current-pair"
            ).textContent = `${symbol} - ${getTimeframeLabel(tf)} Chart`;

            // Clear previous chart
            document.getElementById("chart").innerHTML = "";

            // Load new chart
            new TradingView.widget({
                container_id: "chart",
                autosize: true,
                symbol: symbol,
                interval: tf,
                timezone: "Etc/UTC",
                theme: "dark",
                style: "1",
                locale: "en",
                enable_publishing: false,
                withdateranges: true,
                hide_side_toolbar: false,
                allow_symbol_change: false,
                details: true,
                hotlist: true,
                calendar: false,
                studies: [
                    "RSI@tv-basicstudies",
                    "MACD@tv-basicstudies",
                    "Volume@tv-basicstudies",
                ],
                show_popup_button: true,
                popup_width: "1000",
                popup_height: "650",
            });
        }

        function getTimeframeLabel(value) {
            const labels = {
                15: "15min",
                30: "30min",
                60: "1H",
                240: "4H",
                D: "Daily",
                W: "Weekly",
            };
            return labels[value] || value;
        }

        // Load initial chart on page load
        window.addEventListener("DOMContentLoaded", (event) => {
            loadChart();
        });
    </script>
</body>

</html>