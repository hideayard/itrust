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
                  src="https://widgets.tradingview-widget.com/w/en/tv-market-summary.js"
                ></script>

                <tv-market-summary
                  symbol-sectors='[{"sectionName":"Currency","symbols":["TICKMILL:EURJPY","TICKMILL:EURUSD","FX:USDJPY"]},{"sectionName":"Stocks","symbols":["NASDAQ:AAPL","NASDAQ:ADBE","NASDAQ:NVDA","NASDAQ:TSLA"]},{"sectionName":"Crypto","symbols":["BITSTAMP:BTCUSD","BITSTAMP:ETHUSD","CRYPTO:XRPUSD"]},{"sectionName":"Indices","symbols":["FOREXCOM:SPXUSD","FOREXCOM:NSXUSD","FOREXCOM:DJI","FOREXCOM:UKXGBP"]}]'
                  layout-mode="grid"
                  item-size="compact"
                  mode="custom"
                  style="width: 500px; height: 300px"
                ></tv-market-summary>
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
                      target="_blank"
                      ><span class="blue-text">Economic Calendar</span></a
                    ><span class="trademark"> by TradingView</span>
                  </div>
                  <script
                    type="text/javascript"
                    src="https://s3.tradingview.com/external-embedding/embed-widget-events.js"
                    async
                  >
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
                      target="_blank"
                      ><span class="blue-text">Top stories</span></a
                    ><span class="trademark"> by TradingView</span>
                  </div>
                  <script
                    type="text/javascript"
                    src="https://s3.tradingview.com/external-embedding/embed-widget-timeline.js"
                    async
                  >
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