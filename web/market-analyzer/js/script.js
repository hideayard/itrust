// TradingView Chart Configuration
let currentChart = null;

function loadChart() {
    const symbol = document.getElementById("pair").value;
    const tf = document.getElementById("tf").value;
    const timeframeLabel = getTimeframeLabel(tf);

    // Update current pair display
    document.getElementById("current-pair").textContent = `${symbol} - ${timeframeLabel} Chart`;

    // Clear previous chart
    const chartContainer = document.getElementById("chart");
    chartContainer.innerHTML = "";

    // Load new chart
    currentChart = new TradingView.widget({
        container_id: "chart",
        width: "100%",
        height: "100%",
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
            "MovingAverage@tv-basicstudies"
        ],
        show_popup_button: true,
        popup_width: "1000",
        popup_height: "650",
        toolbar_bg: "#1e222d",
        indicator_width: 1,
        disabled_features: ["use_localstorage_for_settings"],
        enabled_features: ["study_templates"],
        overrides: {
            "paneProperties.background": "#1e222d",
            "paneProperties.vertGridProperties.color": "#2a2e39",
            "paneProperties.horzGridProperties.color": "#2a2e39"
        }
    });

    // Update analysis section
    updateAnalysis(symbol, timeframeLabel);
}

function getTimeframeLabel(value) {
    const labels = {
        15: "15min",
        30: "30min",
        60: "1H",
        240: "4H",
        D: "Daily",
        W: "Weekly",
        M: "Monthly"
    };
    return labels[value] || value;
}

function updateAnalysis(symbol, timeframe) {
    // This is a placeholder function that would typically fetch real analysis data
    const analysisContainer = document.querySelector('.analysis-placeholder');
    if (analysisContainer) {
        // Update with dynamic content based on symbol and timeframe
        const analysisItems = analysisContainer.querySelectorAll('.analysis-item');
        
        // Example updates (in a real app, you'd fetch this data from an API)
        if (analysisItems.length >= 3) {
            // Technical Indicators
            analysisItems[0].innerHTML = `
                <h3>Technical Indicators (${timeframe})</h3>
                <p>RSI: <span class="neutral">54.2 (Neutral)</span></p>
                <p>MACD: <span class="bullish">Bullish Crossover</span></p>
                <p>Volume: <span class="positive">Above Average</span></p>
            `;
            
            // Support & Resistance
            analysisItems[1].innerHTML = `
                <h3>Support & Resistance</h3>
                <p>Support: <span class="support">158.50</span></p>
                <p>Resistance: <span class="resistance">160.20</span></p>
                <p>Pivot: <span class="pivot">159.35</span></p>
            `;
            
            // Market Sentiment
            analysisItems[2].innerHTML = `
                <h3>Market Sentiment</h3>
                <p>Bullish: <span class="bullish">62%</span></p>
                <p>Bearish: <span class="bearish">38%</span></p>
                <p>Volatility: <span class="neutral">Medium</span></p>
            `;
        }
    }
}

// Initialize chart on page load
window.addEventListener('DOMContentLoaded', () => {
    loadChart();
    
    // Add event listeners for dropdowns
    document.getElementById('pair').addEventListener('change', loadChart);
    document.getElementById('tf').addEventListener('change', loadChart);
    
    // Auto-refresh every 5 minutes (optional)
    setInterval(() => {
        if (document.visibilityState === 'visible') {
            loadChart();
        }
    }, 300000);
});

// Handle window resize
window.addEventListener('resize', () => {
    if (currentChart) {
        // TradingView charts automatically handle resizing
    }
});